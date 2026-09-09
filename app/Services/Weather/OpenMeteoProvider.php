<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wetterdaten von Open-Meteo (https://open-meteo.com/).
 *
 * Kein API-Key nötig. Die rohe Antwort wird zwischengespeichert, damit das
 * Widget beim Pollen nicht bei jedem Rendern eine HTTP-Anfrage auslöst.
 */
final class OpenMeteoProvider implements WeatherProvider
{
    public function current(): ?WeatherSnapshot
    {
        $payload = $this->payload();

        return $payload === null ? null : $this->toSnapshot($payload);
    }

    /**
     * Antwort aus dem Cache oder frisch von der API.
     *
     * Fehlschläge werden bewusst nicht gecacht, damit sich das Widget nach
     * einer kurzen Störung von selbst wieder fängt.
     *
     * @return array<string, mixed>|null
     */
    private function payload(): ?array
    {
        $key = $this->cacheKey();

        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $fresh = $this->fetch();

        if ($fresh !== null) {
            Cache::put($key, $fresh, config('dashboard.weather.cache_seconds'));
        }

        return $fresh;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetch(): ?array
    {
        $config = config('dashboard.weather');

        try {
            $response = Http::timeout(5)
                ->retry(2, 250, throw: false)
                ->get($config['endpoint'], [
                    'latitude' => $config['latitude'],
                    'longitude' => $config['longitude'],
                    'timezone' => $config['timezone'],
                    'current' => 'temperature_2m,apparent_temperature,weather_code',
                    'hourly' => 'temperature_2m,weather_code,precipitation_probability',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                    // Mindestens zwei Tage, damit die Stundenvorschau über Mitternacht reicht.
                    'forecast_days' => max(2, (int) $config['forecast_days']),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Open-Meteo nicht erreichbar.', ['message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Open-Meteo antwortete mit einem Fehler.', ['status' => $response->status()]);

            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function toSnapshot(array $payload): ?WeatherSnapshot
    {
        $current = $payload['current'] ?? null;

        if (! is_array($current) || ! isset($current['temperature_2m'])) {
            Log::warning('Open-Meteo lieferte eine Antwort ohne aktuelle Messwerte.');

            return null;
        }

        $timezone = config('dashboard.weather.timezone');

        return new WeatherSnapshot(
            location: config('dashboard.weather.location'),
            temperature: (int) round($current['temperature_2m']),
            apparentTemperature: isset($current['apparent_temperature'])
                ? (int) round($current['apparent_temperature'])
                : null,
            condition: WeatherCondition::fromWmoCode(
                isset($current['weather_code']) ? (int) $current['weather_code'] : null
            ),
            high: $this->rounded($payload['daily']['temperature_2m_max'][0] ?? null),
            low: $this->rounded($payload['daily']['temperature_2m_min'][0] ?? null),
            observedAt: isset($current['time'])
                ? CarbonImmutable::parse($current['time'], $timezone)
                : CarbonImmutable::now($timezone),
            hours: $this->toHours($payload, $timezone),
            days: $this->toDays($payload, $timezone),
        );
    }

    /**
     * Die nächsten vollen Stunden ab jetzt.
     *
     * @param  array<string, mixed>  $payload
     * @return list<HourlyForecast>
     */
    private function toHours(array $payload, string $timezone): array
    {
        $times = $payload['hourly']['time'] ?? [];
        $temperatures = $payload['hourly']['temperature_2m'] ?? [];
        $codes = $payload['hourly']['weather_code'] ?? [];
        $probabilities = $payload['hourly']['precipitation_probability'] ?? [];

        if (! is_array($times) || ! is_array($temperatures)) {
            return [];
        }

        $limit = config('dashboard.weather.forecast_hours');
        $now = CarbonImmutable::now($timezone);
        $hours = [];

        foreach ($times as $index => $time) {
            if (count($hours) >= $limit) {
                break;
            }

            $moment = CarbonImmutable::parse($time, $timezone);

            if ($moment->lessThanOrEqualTo($now) || ! isset($temperatures[$index])) {
                continue;
            }

            $hours[] = new HourlyForecast(
                time: $moment,
                temperature: (int) round($temperatures[$index]),
                condition: WeatherCondition::fromWmoCode(
                    isset($codes[$index]) ? (int) $codes[$index] : null
                ),
                precipitationProbability: isset($probabilities[$index])
                    ? (int) $probabilities[$index]
                    : null,
            );
        }

        return $hours;
    }

    /**
     * Die Tagesvorschau, beginnend mit heute.
     *
     * @param  array<string, mixed>  $payload
     * @return list<DailyForecast>
     */
    private function toDays(array $payload, string $timezone): array
    {
        $dates = $payload['daily']['time'] ?? [];
        $highs = $payload['daily']['temperature_2m_max'] ?? [];
        $lows = $payload['daily']['temperature_2m_min'] ?? [];
        $codes = $payload['daily']['weather_code'] ?? [];

        if (! is_array($dates)) {
            return [];
        }

        $limit = config('dashboard.weather.forecast_days');
        $days = [];

        foreach ($dates as $index => $date) {
            if (count($days) >= $limit) {
                break;
            }

            $days[] = new DailyForecast(
                date: CarbonImmutable::parse($date, $timezone),
                high: $this->rounded($highs[$index] ?? null),
                low: $this->rounded($lows[$index] ?? null),
                condition: WeatherCondition::fromWmoCode(
                    isset($codes[$index]) ? (int) $codes[$index] : null
                ),
            );
        }

        return $days;
    }

    private function rounded(int|float|null $value): ?int
    {
        return $value === null ? null : (int) round($value);
    }

    private function cacheKey(): string
    {
        return sprintf(
            'dashboard.weather.%s,%s',
            config('dashboard.weather.latitude'),
            config('dashboard.weather.longitude'),
        );
    }
}
