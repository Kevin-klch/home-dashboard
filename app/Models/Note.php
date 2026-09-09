<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Note extends Model
{
    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    protected $fillable = ['body', 'pinned_at'];

    protected function casts(): array
    {
        return ['pinned_at' => 'datetime'];
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    public function togglePin(): void
    {
        $this->update(['pinned_at' => $this->isPinned() ? null : now()]);
    }

    /** Erste Zeile, für die Kurzanzeige. */
    public function headline(): string
    {
        return Str::limit(Str::before(trim($this->body), "\n"), 60);
    }

    /** Angeheftetes zuerst, sonst das zuletzt Geänderte. */
    public function scopeInNoteOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('updated_at');
    }
}
