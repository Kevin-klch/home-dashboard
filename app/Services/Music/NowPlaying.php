<?php

namespace App\Services\Music;

final readonly class NowPlaying
{
    public function __construct(
        public string $title,
        public string $artist,
        public ?string $album,
        public ?string $artworkUrl,
        public bool $isPlaying,
        public ?int $progressMs,
        public ?int $durationMs,
        public ?string $url,
        public bool $isPodcast = false,
        public bool $shuffle = false,
        /** "off", "track" oder "context" */
        public string $repeat = 'off',
        public ?string $deviceName = null,
        public ?int $volumePercent = null,
    ) {}

    /** Fortschritt in Prozent, für den Balken. */
    public function progressPercent(): int
    {
        if (! $this->progressMs || ! $this->durationMs) {
            return 0;
        }

        return (int) min(100, round($this->progressMs / $this->durationMs * 100));
    }

    public function positionLabel(): string
    {
        return $this->clock($this->progressMs);
    }

    public function durationLabel(): string
    {
        return $this->clock($this->durationMs);
    }

    public function repeatsOne(): bool
    {
        return $this->repeat === 'track';
    }

    public function repeatsAll(): bool
    {
        return $this->repeat === 'context';
    }

    private function clock(?int $milliseconds): string
    {
        if ($milliseconds === null) {
            return '--:--';
        }

        $seconds = (int) round($milliseconds / 1000);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
