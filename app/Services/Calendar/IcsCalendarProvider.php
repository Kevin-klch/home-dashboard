<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Termine aus einem iCalendar-Feed (.ics).
 *
 * Passt auf jede Quelle, die iCalendar ausliefert: die geheime iCal-Adresse
 * von Google Kalender, ein Freigabelink aus Nextcloud, ein öffentlicher
 * iCloud-Kalender oder eine Datei auf der Platte. Nur lesend.
 *
 * Die Quelle wird übergeben statt aus der Konfiguration gelesen, damit sich
 * mehrere Kalender nebeneinander betreiben lassen – siehe CalendarSources.
 */
final class IcsCalendarProvider implements CalendarProvider
{
    public function __construct(
        private readonly ?string $source,
        private readonly string $timezone = 'Europe/Berlin',
        private readonly int $daysAhead = 30,
        private readonly int $maxEvents = 6,
        private readonly int $cacheSeconds = 900,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->source);
    }

    public function upcoming(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $ics = $this->ics();

        return $ics === null ? null : $this->parse($ics);
    }

    /**
     * Rohen Feed beschaffen.
     *
     * Neben einer URL wird auch ein lokaler Dateipfad akzeptiert – praktisch
     * zum Ausprobieren mit einer exportierten .ics und als Anschluss für
     * Werkzeuge, die eine Datei ablegen.
     */
    private function ics(): ?string
    {
        $source = (string) $this->source;

        if (! $this->isRemote($source)) {
            // Eine lokale Datei ist billig zu lesen, deshalb ohne Cache –
            // Änderungen sind damit sofort sichtbar.
            return $this->read($source);
        }

        $key = 'dashboard.calendar.'.md5($source);

        if (is_string($cached = Cache::get($key))) {
            return $cached;
        }

        // Fehlschläge werden nicht gecacht, damit sich das Widget nach einer
        // Störung von selbst wieder fängt.
        $fresh = $this->download($source);

        if ($fresh !== null) {
            Cache::put($key, $fresh, $this->cacheSeconds);
        }

        return $fresh;
    }

    private function isRemote(string $source): bool
    {
        return str_starts_with($source, 'http://')
            || str_starts_with($source, 'https://')
            || str_starts_with($source, 'webcal://');
    }

    private function read(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            Log::warning('Kalender-Datei nicht lesbar.', ['path' => $path]);

            return null;
        }

        $body = file_get_contents($path);

        return $this->verify($body === false ? '' : $body);
    }

    private function download(string $source): ?string
    {
        // webcal:// ist nur ein anderer Anstrich für https://
        $url = str_starts_with($source, 'webcal://')
            ? 'https://'.substr($source, strlen('webcal://'))
            : $source;

        try {
            $response = Http::timeout(10)
                ->retry(2, 250, throw: false)
                // Google liefert die geheime Adresse teils über eine Weiterleitung aus.
                ->withOptions(['allow_redirects' => true])
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('Kalender-Feed nicht erreichbar.', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Kalender-Feed antwortete mit einem Fehler.', ['status' => $response->status()]);

            return null;
        }

        return $this->verify($response->body());
    }

    /** Schützt davor, eine Fehler- oder Anmeldeseite als Kalender zu lesen. */
    private function verify(string $body): ?string
    {
        if (! str_contains($body, 'BEGIN:VCALENDAR')) {
            Log::warning('Kalenderquelle enthält kein iCalendar-Dokument.');

            return null;
        }

        return $body;
    }

    /**
     * @return list<CalendarEvent>|null
     */
    private function parse(string $ics): ?array
    {
        $timezone = new DateTimeZone($this->timezone);
        $now = CarbonImmutable::now($timezone);
        $from = $now->startOfDay();
        $until = $now->addDays($this->daysAhead)->endOfDay();

        try {
            $calendar = Reader::read($ics, Reader::OPTION_FORGIVING);

            if (! $calendar instanceof VCalendar) {
                return null;
            }

            // Löst Wiederholungsregeln in einzelne Termine auf – dadurch taucht
            // ein jährlicher Geburtstag als konkreter Termin im Zeitfenster auf.
            $expanded = $calendar->expand($from, $until, $timezone);
        } catch (ParseException $e) {
            Log::warning('Kalender-Feed ist kein gültiges iCalendar.', ['message' => $e->getMessage()]);

            return null;
        } catch (Throwable $e) {
            Log::warning('Kalender-Feed konnte nicht ausgewertet werden.', ['message' => $e->getMessage()]);

            return null;
        }

        $events = [];

        foreach ($expanded->VEVENT ?? [] as $vevent) {
            $event = $this->toEvent($vevent, $timezone);

            if ($event !== null && $this->isStillRelevant($event, $now)) {
                $events[] = $event;
            }
        }

        usort($events, fn (CalendarEvent $a, CalendarEvent $b) => $a->start <=> $b->start);

        return array_slice($this->deduplicate($events), 0, $this->maxEvents);
    }

    /**
     * Gleiche Termine zur selben Zeit zusammenfassen.
     *
     * Kalender sammeln über die Jahre Dubletten an – etwa wenn ein Geburtstag
     * mehrfach angelegt oder aus Kontakten synchronisiert wurde. Auf dem
     * Dashboard wäre dieselbe Zeile viermal untereinander nur Rauschen.
     *
     * @param  list<CalendarEvent>  $events
     * @return list<CalendarEvent>
     */
    private function deduplicate(array $events): array
    {
        $seen = [];
        $unique = [];

        foreach ($events as $event) {
            $key = $event->title.'|'.$event->start->toIso8601String().'|'.($event->allDay ? 'd' : 't');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $event;
        }

        return $unique;
    }

    private function toEvent(mixed $vevent, DateTimeZone $timezone): ?CalendarEvent
    {
        if (! isset($vevent->DTSTART)) {
            return null;
        }

        $allDay = ! $vevent->DTSTART->hasTime();

        return new CalendarEvent(
            title: trim((string) ($vevent->SUMMARY ?? '')) ?: 'Ohne Titel',
            start: $this->moment($vevent->DTSTART->getDateTime(), $timezone, $allDay),
            end: isset($vevent->DTEND)
                ? $this->moment($vevent->DTEND->getDateTime(), $timezone, $allDay)
                : null,
            allDay: $allDay,
            location: filled($vevent->LOCATION ?? null) ? trim((string) $vevent->LOCATION) : null,
        );
    }

    /**
     * expand() normalisiert alle Zeitpunkte nach UTC. Terminzeiten müssen
     * deshalb zurückgerechnet werden, ganztägige Termine dagegen nicht – bei
     * ihnen zählt nur das Datum, eine Umrechnung könnte es verschieben.
     */
    private function moment(DateTimeInterface $value, DateTimeZone $timezone, bool $allDay): CarbonImmutable
    {
        return $allDay
            ? CarbonImmutable::parse($value->format('Y-m-d'), $timezone)->startOfDay()
            : CarbonImmutable::instance($value)->setTimezone($timezone);
    }

    /** Vergangenes ausblenden, Laufendes stehen lassen. */
    private function isStillRelevant(CalendarEvent $event, CarbonImmutable $now): bool
    {
        if ($event->allDay) {
            return $event->start->greaterThanOrEqualTo($now->startOfDay());
        }

        return ($event->end ?? $event->start)->greaterThanOrEqualTo($now);
    }
}
