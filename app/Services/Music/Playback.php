<?php

namespace App\Services\Music;

final readonly class Playback
{
    private function __construct(
        public PlaybackStatus $status,
        public ?NowPlaying $track = null,
        /**
         * Was zuletzt lief, wenn gerade nichts läuft.
         *
         * Beim Gerätewechsel meldet Spotify für einige Sekunden Stille –
         * ohne das stünde dann "gerade läuft nichts" statt des Titels.
         */
        public ?NowPlaying $lastTrack = null,
    ) {}

    public static function notConfigured(): self
    {
        return new self(PlaybackStatus::NotConfigured);
    }

    public static function disconnected(): self
    {
        return new self(PlaybackStatus::Disconnected);
    }

    public static function idle(?NowPlaying $lastTrack = null): self
    {
        return new self(PlaybackStatus::Idle, lastTrack: $lastTrack);
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
