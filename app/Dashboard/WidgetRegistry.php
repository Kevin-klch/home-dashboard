<?php

namespace App\Dashboard;

/**
 * Alle Kacheln, die es für das Dashboard gibt.
 *
 * Eine neue Kachel bekannt zu machen heißt: hier einen Eintrag ergänzen.
 * Wo sie liegt und wie groß sie ist, entscheidet danach die Anordnung in
 * der Datenbank.
 */
final class WidgetRegistry
{
    /**
     * @return array<string, array{
     *     label: string,
     *     icon: string,
     *     component: string,
     *     livewire: bool,
     *     widths: list<int>,
     *     width: int,
     *     heights: list<int>,
     *     height: int,
     *     default: bool,
     * }>
     */
    public function all(): array
    {
        return [
            'clock' => [
                'label' => 'Uhr',
                'icon' => 'clock',
                'component' => 'widgets.clock',
                'livewire' => false,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2],
                'height' => 1,
                'default' => true,
            ],
            'weather' => [
                'label' => 'Wetter',
                'icon' => 'sun',
                'component' => 'widgets.weather',
                'livewire' => true,
                // Schmaler als vier Spalten passen Stundenleiste und
                // Wochenspalte nicht nebeneinander.
                'widths' => [4, 6],
                'width' => 4,
                'heights' => [1, 2],
                'height' => 1,
                'default' => true,
            ],
            'calendar' => [
                'label' => 'Termine',
                'icon' => 'calendar',
                'component' => 'widgets.calendar',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2, 3],
                'height' => 1,
                'default' => true,
            ],
            'birthdays' => [
                'label' => 'Geburtstage',
                'icon' => 'cake',
                'component' => 'widgets.birthdays',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2],
                'height' => 1,
                'default' => true,
            ],
            'music' => [
                'label' => 'Musik',
                'icon' => 'music',
                'component' => 'widgets.music',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2],
                'height' => 1,
                'default' => true,
            ],
            'shopping' => [
                'label' => 'Einkaufsliste',
                'icon' => 'cart',
                'component' => 'widgets.shopping-list',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2, 3],
                'height' => 1,
                'default' => true,
            ],
            'tasks' => [
                'label' => 'Aufgaben',
                'icon' => 'check',
                'component' => 'widgets.tasks',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2, 3],
                'height' => 1,
                'default' => true,
            ],
            'waste' => [
                'label' => 'Abfuhr',
                'icon' => 'trash',
                'component' => 'widgets.waste-collection',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2],
                'height' => 1,
                'default' => true,
            ],
            'notes' => [
                'label' => 'Notizen',
                'icon' => 'note',
                'component' => 'widgets.notes',
                'livewire' => true,
                'widths' => [2, 3, 4, 6],
                'width' => 2,
                'heights' => [1, 2, 3],
                'height' => 1,
                'default' => false,
            ],
            'wifi' => [
                'label' => 'WLAN',
                'icon' => 'wifi',
                'component' => 'widgets.wifi-qr',
                'livewire' => true,
                'widths' => [2, 3, 4],
                'width' => 2,
                'heights' => [1, 2],
                'height' => 1,
                'default' => false,
            ],
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array{label: string, icon: string, component: string, livewire: bool, widths: list<int>, width: int, heights: list<int>, height: int, default: bool}|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /** Die Kacheln, die beim ersten Start auf dem Dashboard liegen. */
    public function defaults(): array
    {
        return array_filter($this->all(), fn (array $widget) => $widget['default']);
    }

    /** Erlaubte Höhen einer Kachel in Rasterzeilen. */
    public function heights(string $key): array
    {
        return $this->get($key)['heights'] ?? [1];
    }

    /** Erlaubte Breiten einer Kachel. */
    public function widths(string $key): array
    {
        return $this->get($key)['widths'] ?? [2];
    }

    /** Breite auf das für diese Kachel Erlaubte einrasten. */
    public function clampWidth(string $key, int $width): int
    {
        return $this->clamp($this->widths($key), $width);
    }

    /** Höhe auf das für diese Kachel Erlaubte einrasten. */
    public function clampHeight(string $key, int $height): int
    {
        return $this->clamp($this->heights($key), $height);
    }

    /**
     * @param  list<int>  $allowed
     */
    private function clamp(array $allowed, int $value): int
    {
        if (in_array($value, $allowed, true)) {
            return $value;
        }

        // Auf den nächstliegenden erlaubten Wert gehen.
        usort($allowed, fn ($a, $b) => abs($a - $value) <=> abs($b - $value));

        return $allowed[0];
    }
}
