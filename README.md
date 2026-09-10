# Home Dashboard

Persönliches Dashboard für ein Tablet an der Wand: Wetter, Termine, Geburtstage,
Musik, Einkaufsliste, Aufgaben, Notizen und der WLAN-Zugang als QR-Code.

Laravel 13, Livewire, Tailwind CSS 4, SQLite.

## Einrichten

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

## Konfiguration

Alle Zugangsdaten gehören in die `.env` – **nicht** in die `.env.example`, die
liegt im Repository.

| Schlüssel | Wofür |
|---|---|
| `WEATHER_LOCATION`, `WEATHER_LATITUDE`, `WEATHER_LONGITUDE` | Ort für Open-Meteo, kein API-Key nötig |
| `WEATHER_FORECAST_HOURS`, `WEATHER_VISIBLE_HOURS`, `WEATHER_FORECAST_DAYS` | Umfang der Wettervorhersage |
| `CALENDAR_ICS_URL` | geheime iCal-Adresse des Termin-Kalenders |
| `BIRTHDAYS_ICS_URL` | eigener Geburtstags-Kalender (nicht Googles Systemkalender) |
| `WASTE_ICS_URL` | ICS-Feed des Abfallentsorgers |
| `WIFI_SSID`, `WIFI_PASSWORD`, `WIFI_ENCRYPTION` | Inhalt des WLAN-QR-Codes |
| `SPOTIFY_CLIENT_ID`, `SPOTIFY_CLIENT_SECRET` | aus dem Spotify Developer Dashboard |

### Kalender

Google Kalender → Einstellungen und Freigabe → **Kalender integrieren** →
*Geheime Adresse im iCal-Format*. Wer diese Adresse hat, kann den Kalender
lesen. Ein lokaler Dateipfad oder eine `webcal://`-Adresse funktionieren
ebenfalls.

Für Geburtstage braucht es einen **eigenen, normalen Kalender**. Googles
automatischer Kalender „Geburtstage" ist ein Systemkalender und hat keine
iCal-Adresse.

### Abfuhrkalender

Viele Entsorger bieten ihren Kalender als ICS-Feed an. ENNI liefert ihn für
Moers pro Straße:

```
https://abfallkalender.enni.de/ics-kalender/<strasse>
```

Die Abfallart wird aus dem Termintitel erkannt, Stichwörter statt exakter
Gleichheit – andere Entsorger schreiben „Restmüll" statt „Abholung
Restabfall".

### Spotify

Weiterleitungs-URL im Spotify-Dashboard exakt so eintragen:

```
http://127.0.0.1:8000/spotify/callback
```

`localhost` ist seit April 2025 nicht mehr erlaubt. Das Dashboard muss beim
Verbinden auch über `127.0.0.1` aufgerufen werden, sonst passt das
Sitzungs-Cookie nicht zur Rückleitung.

Steuerung und Gerätewechsel setzen Spotify Premium voraus.

## Dauerbetrieb auf dem iPad

**Zum Homescreen hinzufügen.** In Safari über *Teilen → Zum Home-Bildschirm*.
Dank Manifest und Icon startet das Dashboard dann ohne Browserleisten im
Vollbild.

**Automatische Sperre abschalten.** *Einstellungen → Anzeige & Helligkeit →
Automatische Sperre → Nie*. Die Wake-Lock-API im Dashboard hält den Bildschirm
zusätzlich wach, funktioniert aber nur über HTTPS oder localhost – über eine
einfache `http`-Adresse im Heimnetz greift sie nicht.

**Geführter Zugriff** (*Einstellungen → Bedienungshilfen*) sperrt das Tablet auf
diese eine App, falls Gäste daran vorbeikommen.

**Angemeldet bleiben.** Die Sitzung ist auf 30 Tage gesetzt und „Angemeldet
bleiben" beim Login vorbelegt – damit steht morgens kein Anmeldebildschirm an
der Wand. Bricht die Verbindung ab, zeigt das Dashboard einen Hinweis und lädt
sich neu, sobald der Server wieder antwortet.

**Vor dem echten Dauerbetrieb umstellen:**

```
APP_ENV=production
APP_DEBUG=false
```

Mit `APP_DEBUG=true` zeigt jede Fehlerseite vollständige Stacktraces samt
Konfigurationswerten. Solange nur im Heimnetz entwickelt wird, ist das in
Ordnung – an der Wand nicht.

`php artisan serve` ist der Entwicklungsserver und für Dauerbetrieb nicht
gedacht. Für den Alltag gehört die Anwendung hinter einen richtigen Webserver
oder auf ein Gerät, das ohnehin durchläuft.

## Tests

```bash
php artisan test
```

Die Testsuite setzt keine echten HTTP-Aufrufe ab: `Http::preventStrayRequests()`
in `tests/TestCase.php` lässt jeden Test scheitern, der eine externe Quelle
nicht faked.

## Icons

`public/icons/` wird von `resources/icons/make-icons.php` erzeugt. Das Skript
schreibt die PNG-Struktur von Hand, weil auf dem Entwicklungsrechner weder `gd`
noch ImageMagick vorhanden sind:

```bash
php resources/icons/make-icons.php
```
