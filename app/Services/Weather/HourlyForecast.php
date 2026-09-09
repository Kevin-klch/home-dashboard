<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;

final readonly class HourlyForecast
{
    public function __construct(
        public CarbonImmutable $time,
        public int $temperature,
        public WeatherCondition $condition,
        public ?int $precipitationProbability = null,
    ) {}

    /** Ausführliche Beschriftung, z. B. "15 Uhr". */
    public function label(): string
    {
        return $this->time->format('H').' Uhr';
    }

    /** Nur die Stunde, z. B. "15" – für schmale Spalten. */
    public function shortLabel(): string
    {
        return $this->time->format('H');
    }
}
