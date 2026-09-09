<?php

namespace App\Services\Weather;

use Carbon\CarbonImmutable;

final readonly class DailyForecast
{
    public function __construct(
        public CarbonImmutable $date,
        public ?int $high,
        public ?int $low,
        public WeatherCondition $condition,
    ) {}

    /** "Heute", "Morgen" oder der abgekürzte Wochentag. */
    public function label(): string
    {
        return match (true) {
            $this->date->isToday() => 'Heute',
            $this->date->isTomorrow() => 'Morgen',
            default => $this->date->isoFormat('dd'),
        };
    }

    /** Abgekürzter Wochentag, z. B. "Mo". */
    public function weekday(): string
    {
        return $this->date->isoFormat('dd');
    }

    /** Kurzes Datum, z. B. "11.9." */
    public function shortDate(): string
    {
        return $this->date->format('j.n.');
    }
}
