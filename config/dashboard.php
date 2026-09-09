<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Widget-Konfiguration
    |--------------------------------------------------------------------------
    |
    | Zentrale Stelle für alles, was die Dashboard-Widgets an Einstellungen
    | brauchen. Jedes neue Widget bekommt hier seinen eigenen Abschnitt.
    |
    */

    'weather' => [

        // Open-Meteo braucht keinen API-Key. Kostenlos für nicht-kommerzielle
        // Nutzung, Daten unter CC BY 4.0. https://open-meteo.com/
        'endpoint' => env('WEATHER_ENDPOINT', 'https://api.open-meteo.com/v1/forecast'),

        'location' => env('WEATHER_LOCATION', 'Moers'),
        'latitude' => (float) env('WEATHER_LATITUDE', 51.4534),
        'longitude' => (float) env('WEATHER_LONGITUDE', 6.6326),
        'timezone' => env('WEATHER_TIMEZONE', 'Europe/Berlin'),

        // Wie lange eine Antwort wiederverwendet wird, bevor neu abgefragt wird.
        'cache_seconds' => (int) env('WEATHER_CACHE_SECONDS', 900),

        // Wie viele Stunden die Leiste insgesamt enthält …
        'forecast_hours' => (int) env('WEATHER_FORECAST_HOURS', 24),

        // … und wie viele davon ohne Scrollen zu sehen sind.
        'visible_hours' => (int) env('WEATHER_VISIBLE_HOURS', 6),

        // Anzahl der Tage in der Wochenvorschau. Open-Meteo liefert maximal 16.
        'forecast_days' => (int) env('WEATHER_FORECAST_DAYS', 7),

        // Wie oft der Browser das Widget neu rendert (Livewire-Polling).
        'poll_seconds' => (int) env('WEATHER_POLL_SECONDS', 900),
    ],

];
