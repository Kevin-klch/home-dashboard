<?php

namespace App\Services\Music;

final readonly class Playback
{
    private function __construct(
        public PlaybackStatus $status,
        public ?NowPlaying $track = null,
    ) {}

    public static function notConfigured(): self
    {
        return new self(PlaybackStatus::NotConfigured);
    }

    public static function disconnected(): self
    {
        return new self(PlaybackStatus::Disconnected);
    }

    public static function idle(): self
    {
        return new self(PlaybackStatus::Idle);
    }

    public static function unavailable(): self
    {
        return new self(PlaybackStatus::Unavailable);
    }

    public static function playing(NowPlaying $track): self
    {
        return new self(PlaybackStatus::Playing, $track);
    }

    public function is(PlaybackStatus $status): bool
    {
        return $this->status === $status;
    }
}
