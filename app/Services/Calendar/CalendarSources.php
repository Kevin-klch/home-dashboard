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
    public function appointments(): CalendarProvider
    {
        return $this->make('calendar');
    }

    public function birthdays(): CalendarProvider
    {
        return $this->make('birthdays');
    }

    private function make(string $key): CalendarProvider
    {
        $config = config("dashboard.{$key}");

        return new IcsCalendarProvider(
            source: $config['ics_url'],
            timezone: $config['timezone'],
            daysAhead: (int) $config['days_ahead'],
            maxEvents: (int) $config['max_events'],
            cacheSeconds: (int) $config['cache_seconds'],
        );
    }
}
