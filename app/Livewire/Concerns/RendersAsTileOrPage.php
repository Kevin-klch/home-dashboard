<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Locked;

/**
 * Ein Widget, das es kompakt auf dem Dashboard und ausführlich auf einer
 * eigenen Seite gibt. Die Daten holt die Komponente nur einmal – lediglich
 * die Ansicht unterscheidet sich.
 */
trait RendersAsTileOrPage
{
    private const VARIANTS = ['tile', 'page'];

    // Reine Layout-Details vom Aufrufer – dürfen nicht vom Client kommen.
    #[Locked]
    public string $class = '';

    #[Locked]
    public string $variant = 'tile';

    public function mount(string $class = '', string $variant = 'tile'): void
    {
        $this->class = $class;

        // Der Wert landet im Ansichtsnamen, deshalb nur Bekanntes durchlassen.
        $this->variant = in_array($variant, self::VARIANTS, true) ? $variant : 'tile';
    }

    public function isPage(): bool
    {
        return $this->variant === 'page';
    }

    /** Ansichtsname zur gewählten Variante, etwa "livewire.widgets.tasks-page". */
    protected function variantView(string $base): string
    {
        return "livewire.widgets.{$base}-{$this->variant}";
    }
}
