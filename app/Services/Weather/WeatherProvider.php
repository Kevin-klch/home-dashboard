<?php

namespace App\Services\Weather;

interface WeatherProvider
{
    /**
     * Aktuelles Wetter samt Stundenvorschau.
     *
     * Gibt null zurück, wenn die Quelle nicht erreichbar ist – Aufrufer müssen
     * diesen Fall behandeln, statt sich auf eine Exception zu verlassen.
     */
    public function current(): ?WeatherSnapshot;
}
