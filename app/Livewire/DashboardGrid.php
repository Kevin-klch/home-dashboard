<?php

namespace App\Livewire;

use App\Dashboard\WidgetRegistry;
use App\Models\DashboardWidget;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Das Kachelraster samt Bearbeitungsmodus.
 *
 * Im Normalbetrieb rendert es nur; erst der Stift-Knopf blendet die
 * Steuerelemente zum Verschieben, Verbreitern und Entfernen ein.
 */
class DashboardGrid extends Component
{
    public bool $arranging = false;

    public function toggleArranging(): void
    {
        $this->arranging = ! $this->arranging;
    }

    public function moveEarlier(string $key): void
    {
        $this->swap($key, -1);
    }

    public function moveLater(string $key): void
    {
        $this->swap($key, 1);
    }

    public function widen(string $key, WidgetRegistry $registry): void
    {
        $this->step($key, $registry, 'width', forward: true);
    }

    public function narrow(string $key, WidgetRegistry $registry): void
    {
        $this->step($key, $registry, 'width', forward: false);
    }

    public function taller(string $key, WidgetRegistry $registry): void
    {
        $this->step($key, $registry, 'height', forward: true);
    }

    public function shorter(string $key, WidgetRegistry $registry): void
    {
        $this->step($key, $registry, 'height', forward: false);
    }

    public function remove(string $key): void
    {
        DashboardWidget::query()->where('widget', $key)->delete();

        $this->renumber();
    }

    public function add(string $key, WidgetRegistry $registry): void
    {
        $widget = $registry->get($key);

        if ($widget === null || DashboardWidget::query()->where('widget', $key)->exists()) {
            return;
        }

        DashboardWidget::query()->create([
            'widget' => $key,
            'position' => (int) DashboardWidget::query()->max('position') + 1,
            'width' => $widget['width'],
            'height' => $widget['height'],
        ]);
    }

    public function resetLayout(WidgetRegistry $registry): void
    {
        DashboardWidget::query()->delete();
        DashboardWidget::seedDefaults($registry);
    }

    public function render(WidgetRegistry $registry): View
    {
        $placed = DashboardWidget::layout($registry);

        return view('livewire.dashboard-grid', [
            'placed' => $placed,
            'registry' => $registry,
            // Was noch nicht auf dem Dashboard liegt, lässt sich hinzufügen.
            'available' => array_diff_key($registry->all(), $placed->keyBy('widget')->all()),
        ]);
    }

    // ------------------------------------------------------------------

    /** Eine Kachel um einen Platz nach vorn oder hinten schieben. */
    private function swap(string $key, int $direction): void
    {
        $order = DashboardWidget::query()->inOrder()->get();
        $index = $order->search(fn (DashboardWidget $placed) => $placed->widget === $key);

        if ($index === false) {
            return;
        }

        $target = $index + $direction;

        if ($target < 0 || $target >= $order->count()) {
            return;
        }

        $moved = $order->pull($index);
        $order = $order->values();
        $order->splice($target, 0, [$moved]);

        $this->store($order->values());
    }

    /**
     * Eine Stufe breiter/schmaler oder höher/niedriger.
     *
     * @param  'width'|'height'  $dimension
     */
    private function step(string $key, WidgetRegistry $registry, string $dimension, bool $forward): void
    {
        $placed = DashboardWidget::query()->where('widget', $key)->first();

        if ($placed === null) {
            return;
        }

        $allowed = $dimension === 'width' ? $registry->widths($key) : $registry->heights($key);
        $index = array_search($placed->{$dimension}, $allowed, true);

        // Unbekannter Wert: erst einmal auf etwas Erlaubtes bringen.
        if ($index === false) {
            $placed->update([$dimension => $dimension === 'width'
                ? $registry->clampWidth($key, $placed->width)
                : $registry->clampHeight($key, $placed->height)]);

            return;
        }

        $next = $index + ($forward ? 1 : -1);

        if ($next < 0 || $next >= count($allowed)) {
            return;
        }

        $placed->update([$dimension => $allowed[$next]]);
    }

    /** @param  \Illuminate\Support\Collection<int, DashboardWidget>  $order */
    private function store($order): void
    {
        foreach ($order as $position => $placed) {
            $placed->update(['position' => $position]);
        }
    }

    /** Lücken in den Positionen schließen. */
    private function renumber(): void
    {
        $this->store(DashboardWidget::query()->inOrder()->get());
    }
}
