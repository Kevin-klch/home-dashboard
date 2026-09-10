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

    'night' => [

        // Nachtabsenkung: ab wann und bis wann der Bildschirm gedimmt wird.
        // Der Zeitraum darf über Mitternacht laufen.
        'enabled' => (bool) env('NIGHT_MODE', true),
        'from' => env('NIGHT_FROM', '22:00'),
        'to' => env('NIGHT_TO', '06:30'),

        // Stärke der Absenkung: 0 lässt alles hell, 0.9 ist fast schwarz.
        'dim' => (float) env('NIGHT_DIM', 0.78),

        // Wie lange eine Berührung wieder aufhellt.
        'wake_seconds' => (int) env('NIGHT_WAKE_SECONDS', 60),
    ],

    'security' => [

        // Stellen der PIN fürs Wandtablet.
        'pin_length' => (int) env('PIN_LENGTH', 8),

        // Fehlversuche, bevor gesperrt wird.
        'pin_attempts' => (int) env('PIN_ATTEMPTS', 5),
    ],

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

    'calendar' => [

        // Geheime iCal-Adresse des Termin-Kalenders. In Google Kalender unter
        // Einstellungen → Kalender → "Geheime Adresse im iCal-Format".
        // Ein lokaler Dateipfad wird ebenfalls akzeptiert.
        // Wer diese Adresse hat, kann den Kalender lesen – sie gehört in die
        // .env und nicht ins Repository.
        'ics_url' => env('CALENDAR_ICS_URL'),

        'timezone' => env('CALENDAR_TIMEZONE', 'Europe/Berlin'),
        'days_ahead' => (int) env('CALENDAR_DAYS_AHEAD', 7),
        'max_events' => (int) env('CALENDAR_MAX_EVENTS', 6),
        'cache_seconds' => (int) env('CALENDAR_CACHE_SECONDS', 900),
        'poll_seconds' => (int) env('CALENDAR_POLL_SECONDS', 900),
    ],

    'birthdays' => [

        // Eigener Kalender, nicht der automatische "Geburtstage"-Kalender von
        // Google – der ist ein Systemkalender und hat keine iCal-Adresse.
        // Also einen normalen zweiten Kalender anlegen und dort ganztägige,
        // jährlich wiederkehrende Termine mit dem Namen als Titel eintragen.
        'ics_url' => env('BIRTHDAYS_ICS_URL'),

        'timezone' => env('BIRTHDAYS_TIMEZONE', 'Europe/Berlin'),

        // Ein volles Jahr, damit immer ein nächster Geburtstag gefunden wird.
        'days_ahead' => (int) env('BIRTHDAYS_DAYS_AHEAD', 366),
        'max_events' => (int) env('BIRTHDAYS_MAX_EVENTS', 12),
        'cache_seconds' => (int) env('BIRTHDAYS_CACHE_SECONDS', 3600),
        'poll_seconds' => (int) env('BIRTHDAYS_POLL_SECONDS', 3600),
    ],

    'waste' => [

        // ICS-Feed des Entsorgers. Für Moers liefert ENNI ihn pro Straße:
        // https://abfallkalender.enni.de/ics-kalender/<strasse>
        // Die Adresse verrät die Straße – sie gehört in die .env.
        'ics_url' => env('WASTE_ICS_URL'),

        'timezone' => env('WASTE_TIMEZONE', 'Europe/Berlin'),

        // Der Feed reicht ohnehin nur wenige Monate; großzügig gewählt.
        'days_ahead' => (int) env('WASTE_DAYS_AHEAD', 120),
        'max_events' => (int) env('WASTE_MAX_EVENTS', 30),

        // Abfuhrtermine ändern sich fast nie – einmal am halben Tag genügt.
        'cache_seconds' => (int) env('WASTE_CACHE_SECONDS', 43200),
        'poll_seconds' => (int) env('WASTE_POLL_SECONDS', 1800),
    ],

    'music' => [

        // Wie oft die Anzeige nachfragt. Bei 10 Sekunden sind das drei
        // Anfragen je 30-Sekunden-Fenster – weit unter Spotifys Limit.
        'poll_seconds' => (int) env('MUSIC_POLL_SECONDS', 10),

        // Kurzer Puffer, damit Kachel und Seite nebeneinander nicht doppelt fragen.
        'cache_seconds' => (int) env('MUSIC_CACHE_SECONDS', 5),
    ],

    'wifi' => [

        // Zugangsdaten für den QR-Code an der Wand. Sie stehen nur in der
        // .env – im Repository landen sie nicht.
        'ssid' => env('WIFI_SSID'),
        'password' => env('WIFI_PASSWORD'),

        // WPA (deckt auch WPA2 und WPA3 ab), WEP oder none.
        'encryption' => env('WIFI_ENCRYPTION', 'WPA'),

        // Nur nötig, wenn der Netzname nicht gesendet wird.
        'hidden' => (bool) env('WIFI_HIDDEN', false),
    ],

];
