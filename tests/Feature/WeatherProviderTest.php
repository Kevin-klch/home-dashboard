<?php

namespace Tests\Feature;

use App\Services\Weather\WeatherCondition;
use App\Services\Weather\WeatherProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherProviderTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::parse('2026-09-09 14:20', 'Europe/Berlin');
        $this->travelTo($this->now);
    }

    /**
     * Nachbau einer Open-Meteo-Antwort mit ausrechenbaren Werten:
     * Stundentemperatur = 10 + Index, Tageshoch = 20 + Index, Tagestief = 10 + Index.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $hourly = ['time' => [], 'temperature_2m' => [], 'weather_code' => [], 'precipitation_probability' => []];
        $firstHour = $this->now->startOfHour();

        for ($i = 0; $i < 30; $i++) {
            $hourly['time'][] = $firstHour->addHours($i)->format('Y-m-d\TH:i');
            $hourly['temperature_2m'][] = 10 + $i;
            $hourly['weather_code'][] = 61;
            $hourly['precipitation_probability'][] = 40;
        }

        $daily = ['time' => [], 'weather_code' => [], 'temperature_2m_max' => [], 'temperature_2m_min' => []];

        for ($d = 0; $d < 10; $d++) {
            $daily['time'][] = $this->now->addDays($d)->toDateString();
            $daily['weather_code'][] = 71;
            $daily['temperature_2m_max'][] = 20 + $d;
            $daily['temperature_2m_min'][] = 10 + $d;
        }

        return [
            'current' => [
                'time' => '2026-09-09T14:15',
                'temperature_2m' => 18.4,
                'apparent_temperature' => 16.2,
                'weather_code' => 3,
            ],
            'hourly' => $hourly,
            'daily' => $daily,
        ];
    }

    private function snapshot(): \App\Services\Weather\WeatherSnapshot
    {
        Http::fake(['api.open-meteo.com/*' => Http::response($this->payload())]);

        $snapshot = app(WeatherProvider::class)->current();

        $this->assertNotNull($snapshot);

        return $snapshot;
    }

    public function test_it_returns_the_configured_number_of_hours(): void
    {
        $hours = $this->snapshot()->hours;

        $this->assertCount(24, $hours);
        $this->assertSame(24, config('dashboard.weather.forecast_hours'));
        $this->assertSame(6, config('dashboard.weather.visible_hours'));
    }

    public function test_the_hourly_window_starts_after_the_current_hour(): void
    {
        $hours = $this->snapshot()->hours;

        // 14:00 liegt hinter uns, die Reihe beginnt also bei 15:00 …
        $this->assertSame('2026-09-09 15:00', $hours[0]->time->format('Y-m-d H:i'));
        $this->assertSame(11, $hours[0]->temperature);

        // … und läuft über Mitternacht hinaus bis 14:00 des Folgetags.
        $this->assertSame('2026-09-10 14:00', $hours[23]->time->format('Y-m-d H:i'));
        $this->assertSame(34, $hours[23]->temperature);
    }

    public function test_it_maps_the_hourly_details(): void
    {
        $hour = $this->snapshot()->hours[0];

        $this->assertSame('15', $hour->shortLabel());
        $this->assertSame('15 Uhr', $hour->label());
        $this->assertSame(WeatherCondition::Rain, $hour->condition);
        $this->assertSame(40, $hour->precipitationProbability);
    }

    public function test_it_returns_seven_days_starting_today(): void
    {
        $days = $this->snapshot()->days;

        $this->assertCount(7, $days);
        $this->assertSame(7, config('dashboard.weather.forecast_days'));
        $this->assertTrue($days[0]->date->isToday());
        $this->assertSame('2026-09-15', $days[6]->date->toDateString());
    }

    public function test_it_labels_the_first_days_in_words(): void
    {
        $days = $this->snapshot()->days;

        $this->assertSame('Heute', $days[0]->label());
        $this->assertSame('Morgen', $days[1]->label());
        $this->assertSame('9.9.', $days[0]->shortDate());

        // Ab übermorgen der abgekürzte Wochentag – der 11.9.2026 ist ein Freitag.
        $this->assertSame('Fr', $days[2]->label());
    }

    public function test_it_maps_the_daily_details(): void
    {
        $days = $this->snapshot()->days;

        $this->assertSame(20, $days[0]->high);
        $this->assertSame(10, $days[0]->low);
        $this->assertSame(26, $days[6]->high);
        $this->assertSame(WeatherCondition::Snow, $days[0]->condition);
    }

    public function test_it_asks_for_enough_days_to_cover_the_forecast(): void
    {
        $this->snapshot();

        Http::assertSent(fn ($request) => (int) $request['forecast_days'] === 7
            && str_contains($request['daily'], 'weather_code'));
    }
}
