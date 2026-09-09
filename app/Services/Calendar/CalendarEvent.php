<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;

final readonly class CalendarEvent
{
    public function __construct(
        public string $title,
        public CarbonImmutable $start,
        public ?CarbonImmutable $end,
        public bool $allDay,
        public ?string $location = null,
    ) {}

    /** Gruppierungsschlüssel für die Tagesüberschriften im Widget. */
    public function dayKey(): string
    {
        return $this->start->toDateString();
    }

    /** "Heute", "Morgen" oder z. B. "Fr 11.9." – mit Jahr, wenn es nicht das laufende ist. */
    public function dayLabel(): string
    {
        return match (true) {
            $this->start->isToday() => 'Heute',
            $this->start->isTomorrow() => 'Morgen',
            default => $this->start->isoFormat('dd').' '
                .$this->start->format($this->start->isCurrentYear() ? 'j.n.' : 'j.n.Y'),
        };
    }

    /** "Ganztägig" oder z. B. "16:30 – 17:15" */
    public function timeLabel(): string
    {
        if ($this->allDay) {
            return 'Ganztägig';
        }

        $label = $this->start->format('H:i');

        // Endzeiten nur zeigen, wenn sie am selben Tag liegen – sonst wird die
        // Zeile im schmalen Widget unlesbar.
        if ($this->end !== null && $this->end->isSameDay($this->start) && $this->end->ne($this->start)) {
            $label .= ' – '.$this->end->format('H:i');
        }

        return $label;
    }

    /** Läuft der Termin gerade? */
    public function isRunning(CarbonImmutable $now): bool
    {
        if ($this->allDay) {
            return $this->start->isSameDay($now);
        }

        return $this->start->lessThanOrEqualTo($now)
            && $this->end !== null
            && $this->end->greaterThan($now);
    }
}
