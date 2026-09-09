<?php

namespace App\Services\Music;

enum PlaybackStatus
{
    /** Zugangsdaten fehlen – die Verbindung wurde nie eingerichtet. */
    case NotConfigured;

    /** Eingerichtet, aber noch nicht mit einem Konto verbunden. */
    case Disconnected;

    /** Verbunden, es läuft gerade nichts. */
    case Idle;

    /** Verbunden, aber Spotify antwortet nicht wie erwartet. */
    case Unavailable;

    /** Es läuft etwas – oder ist pausiert, das steht dann im Titel. */
    case Playing;
}
