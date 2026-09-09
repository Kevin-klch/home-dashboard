<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;

final readonly class WeatherSnapshot
{
    /**
     * @param  list<HourlyForecast>  $hours
     * @param  list<DailyForecast>  $days
     */
    public function __construct(
        public string $location,
        public int $temperature,
        public ?int $apparentTemperature,
        public WeatherCondition $condition,
        public ?int $high,
        public ?int $low,
        public CarbonImmutable $observedAt,
        public array $hours = [],
        public array $days = [],
    ) {}
}
