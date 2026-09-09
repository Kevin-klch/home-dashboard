<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingItem extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingItemFactory> */
    use HasFactory;

    protected $fillable = ['name', 'note', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    public function toggle(): void
    {
        $this->update(['completed_at' => $this->isDone() ? null : now()]);
    }

    /** Offene zuerst, innerhalb der Gruppen die ältesten oben. */
    public function scopeInShoppingOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('completed_at IS NOT NULL')
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
