<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Services\Music\ControlResult;
use App\Services\Music\PlaybackStatus;
use App\Services\Music\SpotifyClient;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Music extends Component
{
    use RendersAsTileOrPage;

    /** Rückmeldung eines Befehls, etwa "kein aktives Gerät". */
    public ?string $notice = null;

    /** Geräteauswahl offen? */
    public bool $showDevices = false;

    /**
     * Zuletzt geladene Geräte. Wird nur beim Öffnen der Auswahl gefüllt,
     * damit das Pollen nicht dauernd die Geräteliste mitzieht.
     *
     * @var list<array{id: string, name: string, type: string, icon: string, active: bool, restricted: bool}>
     */
    #[Locked]
    public array $devices = [];

    // ------------------------------------------------------------------
    // Steuerung
    // ------------------------------------------------------------------

    public function togglePlay(SpotifyClient $spotify): void
    {
        $track = $spotify->playback()->track;

        $this->report($track?->isPlaying ? $spotify->pause() : $spotify->play());
    }

    public function next(SpotifyClient $spotify): void
    {
        $this->report($spotify->next());
    }

    public function previous(SpotifyClient $spotify): void
    {
        $this->report($spotify->previous());
    }

    public function toggleShuffle(SpotifyClient $spotify): void
    {
        $track = $spotify->playback()->track;

        $this->report($spotify->shuffle(! ($track?->shuffle ?? false)));
    }

    /** aus → alles → einer → aus */
    public function cycleRepeat(SpotifyClient $spotify): void
    {
        $current = $spotify->playback()->track?->repeat ?? 'off';

        $this->report($spotify->repeat(match ($current) {
            'off' => 'context',
            'context' => 'track',
            default => 'off',
        }));
    }

    public function setVolume(SpotifyClient $spotify, int $percent): void
    {
        $this->report($spotify->volume($percent));
    }

    /** Position in Prozent des Titels, kommt vom Klick auf den Balken. */
    public function seekToPercent(SpotifyClient $spotify, int $percent): void
    {
        $duration = $spotify->playback()->track?->durationMs;

        if ($duration === null) {
            return;
        }

        $this->report($spotify->seek((int) round($duration * max(0, min(100, $percent)) / 100)));
    }

    // ------------------------------------------------------------------
    // Ausgabegerät
    // ------------------------------------------------------------------

    public function toggleDevices(SpotifyClient $spotify): void
    {
        $this->showDevices = ! $this->showDevices;
        $this->notice = null;

        if (! $this->showDevices) {
            return;
        }

        $devices = $spotify->devices();

        if ($devices === null) {
            $this->notice = 'Die Geräteliste ließ sich nicht laden.';
            $this->devices = [];

            return;
        }

        $this->devices = array_map(fn ($device) => $device->toArray(), $devices);

        if ($this->devices === []) {
            $this->notice = 'Kein Gerät gefunden. Öffne Spotify einmal auf dem gewünschten Gerät.';
        }
    }

    public function transferTo(SpotifyClient $spotify, string $deviceId): void
    {
        $this->report($spotify->transferTo($deviceId));

        if ($this->notice === null) {
            $this->showDevices = false;
        }
    }

    private function report(ControlResult $result): void
    {
        $this->notice = $result->message();
    }

    // ------------------------------------------------------------------

    public function render(SpotifyClient $spotify): View
    {
        $playback = $spotify->playback();

        return view($this->variantView('music'), [
            'playback' => $playback,
            'track' => $playback->track,
            // Beim Gerätewechsel meldet Spotify kurz Stille – dann bleibt der
            // zuletzt gespielte Titel stehen statt "gerade läuft nichts".
            'last' => $playback->lastTrack,
            'canControl' => $spotify->canControl(),
            'canSeeHistory' => $spotify->canSeeHistory(),
            // Nur die Seite hat Platz für den Verlauf: rund 15 sind sichtbar,
            // der Rest lässt sich in der Spalte scrollen.
            'history' => $this->isPage() ? $spotify->recentlyPlayed(30) : null,
            // Nur nachfragen, wenn es überhaupt etwas zu holen gibt.
            'poll' => $playback->is(PlaybackStatus::NotConfigured) || $playback->is(PlaybackStatus::Disconnected)
                ? null
                : config('dashboard.music.poll_seconds'),
        ]);
    }
}
