<?php

namespace App\Services\Music;

interface NowPlayingProvider
{
    /**
     * Was gerade läuft – oder warum nichts angezeigt werden kann.
     *
     * Gibt bewusst nie null zurück: "nichts eingerichtet", "nichts verbunden",
     * "gerade Stille" und "Dienst gestört" sind vier verschiedene Aussagen und
     * sollen auf dem Dashboard auch unterschiedlich aussehen.
     */
    public function playback(): Playback;
}
