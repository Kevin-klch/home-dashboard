<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = ['name', 'due_on', 'completed_at'];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    public function toggle(): void
    {
        $this->update(['completed_at' => $this->isDone() ? null : now()]);
    }

    /** Überfällig oder heute fällig – beides verlangt Aufmerksamkeit. */
    public function isUrgent(): bool
    {
        return ! $this->isDone()
            && $this->due_on !== null
            && $this->due_on->startOfDay()->lessThanOrEqualTo(now()->startOfDay());
    }

    /** "Heute", "Morgen", "Überfällig" oder der Wochentag mit Datum. */
    public function dueLabel(): ?string
    {
        if ($this->due_on === null) {
            return null;
        }

        return match (true) {
            $this->due_on->isToday() => 'Heute',
            $this->due_on->isTomorrow() => 'Morgen',
            $this->due_on->isPast() => 'Überfällig',
            default => $this->due_on->isoFormat('dd').' '
                .$this->due_on->format($this->due_on->isCurrentYear() ? 'j.n.' : 'j.n.Y'),
        };
    }

    /**
     * Offene zuerst, darin die mit Termin nach Fälligkeit; Aufgaben ohne
     * Datum wandern ans Ende der offenen Gruppe.
     */
    public function scopeInTaskOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('completed_at IS NOT NULL')
            ->orderByRaw('due_on IS NULL')
            ->orderBy('due_on')
            ->orderBy('created_at');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }
}
