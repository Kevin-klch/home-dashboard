<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Weather;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WeatherWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feste Uhrzeit, damit die Stundenvorschau vorhersagbar bleibt.
        $this->travelTo(CarbonImmutable::parse('2026-09-09 14:20', 'Europe/Berlin'));
    }

    /**
     * Ausschnitt einer echten Open-Meteo-Antwort.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'current' => [
                'time' => '2026-09-09T14:15',
                'temperature_2m' => 18.4,
                'apparent_temperature' => 16.2,
                'weather_code' => 3,
            ],
            'hourly' => [
                'time' => [
                    '2026-09-09T13:00', '2026-09-09T14:00', '2026-09-09T15:00',
                    '2026-09-09T16:00', '2026-09-09T17:00', '2026-09-09T18:00',
                ],
                'temperature_2m' => [17.6, 18.1, 19.2, 18.8, 17.4, 16.1],
                'weather_code' => [2, 3, 61, 80, 0, 95],
                'precipitation_probability' => [5, 10, 60, 40, 5, 30],
            ],
            'daily' => [
                'time' => ['2026-09-09', '2026-09-10'],
                'weather_code' => [3, 61],
                'temperature_2m_max' => [21.4, 19.0],
                'temperature_2m_min' => [10.6, 12.0],
            ],
        ];
    }

    public function test_it_shows_the_current_conditions(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class)
            ->assertSee('18')          // Temperatur, gerundet aus 18.4
            ->assertSee('Bedeckt')     // WMO-Code 3
            ->assertSee('16')          // gefühlte Temperatur
            ->assertSee('21')          // Tageshoch
            ->assertSee('11')          // Tagestief, gerundet aus 10.6
            ->assertSee('Moers')
            ->assertSee('Open-Meteo');
    }

    public function test_it_only_lists_hours_that_are_still_ahead(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class)
            ->assertSeeHtml('data-hour="15"')
            ->assertSeeHtml('data-hour="18"')
            ->assertDontSeeHtml('data-hour="13"')   // liegt vor der eingefrorenen Uhrzeit
            ->assertDontSeeHtml('data-hour="14"');
    }

    public function test_it_shows_the_chance_of_rain_only_when_it_is_relevant(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class)
            ->assertSee('60%')          // 15 Uhr
            ->assertSee('40%')          // 16 Uhr
            ->assertSee('30%')          // 18 Uhr, genau auf der Schwelle
            ->assertDontSee('5%');      // 17 Uhr, darunter
    }

    public function test_it_falls_back_when_the_api_returns_an_error(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response('', 500)]);

        Livewire::test(Weather::class)
            ->assertSee('Wetterdaten nicht verfügbar')
            ->assertDontSee('Gefühlt');
    }

    public function test_it_falls_back_when_the_api_cannot_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('Keine Verbindung'));

        Livewire::test(Weather::class)->assertSee('Wetterdaten nicht verfügbar');
    }

    public function test_it_reuses_the_cached_response(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class)->assertSee('Bedeckt');
        Livewire::test(Weather::class)->assertSee('Bedeckt');

        Http::assertSentCount(1);
    }

    public function test_it_does_not_cache_a_failure(): void
    {
        // Ein zweiter Http::fake()-Aufruf wuerde den ersten Stub nicht ersetzen,
        // deshalb steuert ein Schalter, was die Quelle gerade zurueckgibt.
        $unavailable = true;

        // Referenz, nicht Wert: eine Arrow-Funktion wuerde $unavailable einfrieren.
        Http::fake(function () use (&$unavailable) {
            return $unavailable
                ? Http::response('', 503)
                : Http::response($this->payload());
        });

        Livewire::test(Weather::class)->assertSee('Wetterdaten nicht verfügbar');

        $unavailable = false;

        Livewire::test(Weather::class)->assertSee('Bedeckt');
    }

    public function test_it_asks_open_meteo_for_the_configured_location(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), config('dashboard.weather.endpoint'))
                && $request['latitude'] == config('dashboard.weather.latitude')
                && $request['longitude'] == config('dashboard.weather.longitude')
                && $request['timezone'] === config('dashboard.weather.timezone');
        });
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        // Livewire reicht "class" nicht automatisch an das Wurzelelement weiter,
        // sondern nur an mount() – ohne das faellt die Kachel im Raster zusammen.
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }

    public function test_it_shows_the_week_column_inside_the_widget(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        Livewire::test(Weather::class)
            ->assertSeeHtml('data-day="2026-09-09"')
            ->assertSeeHtml('data-day="2026-09-10"')
            ->assertSee('Mi')      // 9.9.2026 ist ein Mittwoch
            ->assertSee('21°')     // Hoch heute
            ->assertSee('11°');    // Tief heute
    }

    public function test_the_hourly_strip_shows_the_configured_number_of_columns(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        // Sichtbar sind sechs Spalten, gescrollt wird über alle geladenen Stunden.
        Livewire::test(Weather::class)
            ->assertSeeHtml('width: calc(100% / 6)');
    }
}
