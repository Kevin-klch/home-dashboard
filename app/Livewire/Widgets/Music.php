<?php

namespace App\Livewire\Widgets;

use App\Services\Music\ControlResult;
use App\Services\Music\PlaybackStatus;
use App\Services\Music\SpotifyClient;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Music extends Component
{
    private const VARIANTS = ['tile', 'page'];

    // Reine Layout-Details vom Aufrufer – dürfen vom Client nicht kommen.
    #[Locked]
    public string $class = '';

    #[Locked]
    public string $variant = 'tile';

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

    public function mount(string $class = '', string $variant = 'tile'): void
    {
        $this->class = $class;

        // Der Wert landet im Ansichtsnamen, deshalb nur Bekanntes durchlassen.
        $this->variant = in_array($variant, self::VARIANTS, true) ? $variant : 'tile';
    }

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

        return view("livewire.widgets.music-{$this->variant}", [
            'playback' => $playback,
            'track' => $playback->track,
            'canControl' => $spotify->canControl(),
            // Nur nachfragen, wenn es überhaupt etwas zu holen gibt.
            'poll' => $playback->is(PlaybackStatus::NotConfigured) || $playback->is(PlaybackStatus::Disconnected)
                ? null
                : config('dashboard.music.poll_seconds'),
        ]);
    }
}
