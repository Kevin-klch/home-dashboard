<?php

namespace App\Services\Calendar;

/**
 * Baut die Kalenderquellen des Dashboards.
 *
 * Termine und Geburtstage liegen in getrennten Kalendern und brauchen deshalb
 * je eigene Adresse und ein eigenes Zeitfenster. Ein Wechsel der Technik –
 * etwa auf CalDAV – wäre eine neue Klasse und eine Zeile in make().
 */
final class CalendarSources
{
    /**
     * @param  int|null  $daysAhead  überschreibt das Zeitfenster aus der Konfiguration
     * @param  int|null  $maxEvents  überschreibt die Höchstzahl der Termine
     */
    public function appointments(?int $daysAhead = null, ?int $maxEvents = null): CalendarProvider
    {
        return $this->make('calendar', $daysAhead, $maxEvents);
    }

    public function birthdays(): CalendarProvider
    {
        return $this->make('birthdays');
    }

    private function make(string $key, ?int $daysAhead = null, ?int $maxEvents = null): CalendarProvider
    {
        $config = config("dashboard.{$key}");

        return new IcsCalendarProvider(
            source: $config['ics_url'],
            timezone: $config['timezone'],
            daysAhead: $daysAhead ?? (int) $config['days_ahead'],
            maxEvents: $maxEvents ?? (int) $config['max_events'],
            cacheSeconds: (int) $config['cache_seconds'],
        );
    }
}
