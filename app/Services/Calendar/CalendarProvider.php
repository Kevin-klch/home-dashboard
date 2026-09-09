<?php

namespace App\Services\Calendar;

interface CalendarProvider
{
    /**
     * Die nächsten anstehenden Termine, aufsteigend sortiert.
     *
     * Gibt null zurück, wenn keine Quelle eingerichtet oder sie nicht
     * erreichbar ist – Aufrufer müssen diesen Fall behandeln.
     *
     * @return list<CalendarEvent>|null
     */
    public function upcoming(): ?array;

    /** Ist überhaupt eine Quelle hinterlegt? */
    public function isConfigured(): bool;
}
