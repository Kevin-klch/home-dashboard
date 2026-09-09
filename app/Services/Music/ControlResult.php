<?php

namespace App\Services\Music;

enum ControlResult
{
    case Ok;

    /** Kein Gerät aktiv – Spotify weiß nicht, wo es abspielen soll. */
    case NoDevice;

    /** Der Zugriff erlaubt kein Steuern (fehlender Scope oder kein Premium). */
    case NotAllowed;

    case Failed;

    public function message(): ?string
    {
        return match ($this) {
            self::Ok => null,
            self::NoDevice => 'Kein aktives Gerät. Starte die Wiedergabe einmal auf einem Gerät.',
            self::NotAllowed => 'Zum Steuern fehlt die Berechtigung. Bitte die Verbindung erneuern.',
            self::Failed => 'Spotify hat den Befehl nicht angenommen.',
        };
    }
}
