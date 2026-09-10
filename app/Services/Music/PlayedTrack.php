<?php

namespace App\Services\Music;

use Carbon\CarbonImmutable;

final readonly class PlayedTrack
{
    public function __construct(
        public string $title,
        public string $artist,
        public ?string $artworkUrl,
        public ?string $url,
        public CarbonImmutable $playedAt,
    ) {}

    /** "gerade eben", "vor 12 Min." oder die Uhrzeit. */
    public function whenLabel(): string
    {
        $minutes = (int) $this->playedAt->diffInMinutes(CarbonImmutable::now($this->playedAt->getTimezone()));

        return match (true) {
            $minutes < 1 => 'gerade eben',
            $minutes < 60 => "vor {$minutes} Min.",
            $this->playedAt->isToday() => $this->playedAt->format('H:i').' Uhr',
            $this->playedAt->isYesterday() => 'gestern '.$this->playedAt->format('H:i'),
            default => $this->playedAt->isoFormat('dd').' '.$this->playedAt->format('H:i'),
        };
    }
}
