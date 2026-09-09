<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Calendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarWidgetTest extends TestCase
{
    private const URL = 'https://calendar.google.com/calendar/ical/geheim/basic.ics';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-09 14:20', 'Europe/Berlin'));
        config(['dashboard.calendar.ics_url' => self::URL]);
    }

    /**
     * Feed mit den Fällen, die im Alltag vorkommen: ein abgelaufener Termin,
     * ein laufender, ein späterer, ein jährlicher Geburtstag und einer weit
     * außerhalb des Zeitfensters.
     */
    private function ics(): string
    {
        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Google Inc//Google Calendar//EN',

            'BEGIN:VEVENT',
            'UID:vorbei@test',
            'DTSTART;TZID=Europe/Berlin:20260909T090000',
            'DTEND;TZID=Europe/Berlin:20260909T093000',
            'SUMMARY:Frühstück',
            'END:VEVENT',

            'BEGIN:VEVENT',
            'UID:laeuft@test',
            'DTSTART;TZID=Europe/Berlin:20260909T140000',
            'DTEND;TZID=Europe/Berlin:20260909T150000',
            'SUMMARY:Standup',
            'END:VEVENT',

            'BEGIN:VEVENT',
            'UID:zahnarzt@test',
            'DTSTART;TZID=Europe/Berlin:20260909T163000',
            'DTEND;TZID=Europe/Berlin:20260909T171500',
            'SUMMARY:Zahnarzt',
            'LOCATION:Moers',
            'END:VEVENT',

            'BEGIN:VEVENT',
            'UID:geburtstag@test',
            'DTSTART;VALUE=DATE:19900910',
            'DTEND;VALUE=DATE:19900911',
            'RRULE:FREQ=YEARLY',
            'SUMMARY:Geburtstag Anna',
            'END:VEVENT',

            'BEGIN:VEVENT',
            'UID:sport@test',
            'DTSTART;TZID=Europe/Berlin:20260912T190000',
            'DTEND;TZID=Europe/Berlin:20260912T203000',
            'SUMMARY:Sport',
            'END:VEVENT',

            'BEGIN:VEVENT',
            'UID:weit-weg@test',
            'DTSTART;TZID=Europe/Berlin:20261110T100000',
            'DTEND;TZID=Europe/Berlin:20261110T110000',
            'SUMMARY:Weit in der Zukunft',
            'END:VEVENT',

            'END:VCALENDAR',
            '',
        ]);
    }

    private function fakeFeed(): void
    {
        Http::fake(['calendar.google.com/*' => Http::response($this->ics())]);
    }

    public function test_it_shows_the_upcoming_events(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class)
            ->assertSee('Standup')
            ->assertSee('Zahnarzt')
            ->assertSee('Geburtstag Anna')
            ->assertSee('Sport');
    }

    public function test_it_keeps_local_time_instead_of_utc(): void
    {
        // expand() aus sabre/vobject normalisiert nach UTC – ohne Rückrechnung
        // stünde hier 14:30 statt 16:30.
        $this->fakeFeed();

        Livewire::test(Calendar::class)
            ->assertSee('16:30 – 17:15')
            ->assertDontSee('14:30');
    }

    public function test_it_hides_events_that_are_already_over(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class)->assertDontSee('Frühstück');
    }

    public function test_it_keeps_an_event_that_is_running_right_now(): void
    {
        $this->fakeFeed();

        // Beginn 14:00, Ende 15:00, jetzt ist 14:20.
        Livewire::test(Calendar::class)->assertSee('Standup');
    }

    public function test_it_ignores_events_beyond_the_configured_window(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class)->assertDontSee('Weit in der Zukunft');
    }

    public function test_it_expands_a_yearly_birthday_into_the_window(): void
    {
        $this->fakeFeed();

        // Angelegt 1990, muss als ganztägiger Termin am 10.9.2026 auftauchen.
        Livewire::test(Calendar::class)
            ->assertSee('Geburtstag Anna')
            ->assertSee('Ganztägig')
            ->assertSeeHtml('data-event="2026-09-10"');
    }

    public function test_it_groups_events_under_day_headings(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class)
            ->assertSee('Heute')
            ->assertSee('Morgen')
            ->assertSee('Sa 12.9.');
    }

    public function test_it_sorts_events_chronologically(): void
    {
        $this->fakeFeed();

        $html = Livewire::test(Calendar::class)->html();

        $this->assertLessThan(strpos($html, 'Zahnarzt'), strpos($html, 'Standup'));
        $this->assertLessThan(strpos($html, 'Geburtstag Anna'), strpos($html, 'Zahnarzt'));
        $this->assertLessThan(strpos($html, 'Sport'), strpos($html, 'Geburtstag Anna'));
    }

    public function test_it_shows_the_location_when_there_is_one(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class)->assertSee('Moers');
    }

    public function test_it_asks_for_setup_when_no_url_is_configured(): void
    {
        config(['dashboard.calendar.ics_url' => null]);

        Livewire::test(Calendar::class)
            ->assertSee('Kalender noch nicht verbunden')
            ->assertSee('CALENDAR_ICS_URL');

        Http::assertNothingSent();
    }

    public function test_it_falls_back_when_the_feed_is_unreachable(): void
    {
        Http::fake(['calendar.google.com/*' => Http::response('', 500)]);

        Livewire::test(Calendar::class)->assertSee('Kalender nicht erreichbar');
    }

    public function test_it_falls_back_when_the_feed_is_not_icalendar(): void
    {
        Http::fake(['calendar.google.com/*' => Http::response('<html>Anmeldung erforderlich</html>')]);

        Livewire::test(Calendar::class)->assertSee('Kalender nicht erreichbar');
    }

    public function test_it_reports_an_empty_calendar_separately(): void
    {
        Http::fake(['calendar.google.com/*' => Http::response(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//EN\r\nEND:VCALENDAR\r\n"
        )]);

        Livewire::test(Calendar::class)
            ->assertSee('Nichts geplant')
            ->assertDontSee('nicht erreichbar');
    }

    public function test_it_reuses_the_cached_feed(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class);
        Livewire::test(Calendar::class);

        Http::assertSentCount(1);
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }

    public function test_it_collapses_duplicate_entries(): void
    {
        // Derselbe jährliche Geburtstag doppelt angelegt – kommt in echten
        // Kalendern durch Kontakt-Synchronisierung vor.
        $doubled = str_replace(
            'UID:geburtstag@test',
            'UID:geburtstag-kopie@test',
            $this->ics()
        );
        $merged = str_replace('END:VCALENDAR', substr($doubled, strpos($doubled, 'BEGIN:VEVENT')), $this->ics());

        Http::fake(['calendar.google.com/*' => Http::response($merged)]);

        $html = Livewire::test(Calendar::class)->html();

        $this->assertSame(1, substr_count($html, 'Geburtstag Anna'));
    }

    public function test_it_shows_the_year_for_dates_outside_the_current_one(): void
    {
        config(['dashboard.calendar.days_ahead' => 400]);

        Http::fake(['calendar.google.com/*' => Http::response(implode("

", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//EN',
            'BEGIN:VEVENT',
            'UID:naechstes-jahr@test',
            'DTSTART;VALUE=DATE:19900627',
            'DTEND;VALUE=DATE:19900628',
            'RRULE:FREQ=YEARLY',
            'SUMMARY:Geburtstag Kevin',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]))]);

        // Der nächste 27. Juni liegt 2027 – ohne Jahresangabe wäre "So 27.6."
        // auf dem Dashboard nicht einzuordnen.
        Livewire::test(Calendar::class)->assertSee('So 27.6.2027');
    }

    public function test_the_page_looks_further_ahead_than_the_tile(): void
    {
        // 20 Tage voraus: ausserhalb der 7 Tage der Kachel, innerhalb der 30 der Seite.
        Http::fake(['calendar.google.com/*' => Http::response(implode("
", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//EN',
            'BEGIN:VEVENT',
            'UID:spaet@test',
            'DTSTART;TZID=Europe/Berlin:20260929T100000',
            'DTEND;TZID=Europe/Berlin:20260929T110000',
            'SUMMARY:Spaeter Termin',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]))]);

        Livewire::test(Calendar::class, ['variant' => 'tile'])->assertDontSee('Spaeter Termin');
        Livewire::test(Calendar::class, ['variant' => 'page'])->assertSee('Spaeter Termin');
    }

    public function test_the_page_groups_events_under_day_headings(): void
    {
        $this->fakeFeed();

        Livewire::test(Calendar::class, ['variant' => 'page'])
            ->assertOk()
            ->assertSee('Heute')
            ->assertSee('Zahnarzt')
            ->assertSeeHtml('data-event="2026-09-09"');
    }
}
