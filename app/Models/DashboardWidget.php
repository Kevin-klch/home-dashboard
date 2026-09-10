<?php

namespace App\Models;

use App\Dashboard\WidgetRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eine platzierte Kachel: welche, an welcher Stelle, wie breit und wie hoch.
 *
 * Wie die übrigen Daten des Dashboards ohne Benutzerbezug – die Anordnung
 * gilt für das Gerät an der Wand, nicht pro Person.
 */
class DashboardWidget extends Model
{
    protected $fillable = ['widget', 'position', 'width', 'height'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function scopeInOrder(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Die Anordnung holen und dabei aufräumen.
     *
     * Beim ersten Start wird die Standardanordnung angelegt. Einträge zu
     * Kacheln, die es nicht mehr gibt, werden übergangen – sonst würde eine
     * umbenannte Kachel das ganze Dashboard lahmlegen.
     *
     * @return Collection<int, self>
     */
    public static function layout(WidgetRegistry $registry): Collection
    {
        if (static::query()->doesntExist()) {
            static::seedDefaults($registry);
        }

        return static::query()
            ->inOrder()
            ->get()
            ->filter(fn (self $placed) => $registry->has($placed->widget))
            ->values();
    }

    public static function seedDefaults(WidgetRegistry $registry): void
    {
        $position = 0;

        foreach ($registry->defaults() as $key => $widget) {
            static::query()->updateOrCreate(
                ['widget' => $key],
                [
                    'position' => $position++,
                    'width' => $widget['width'],
                    'height' => $widget['height'],
                ],
            );
        }
    }
}
