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

    /**
     * Größe der Kachel im Raster.
     *
     * Damit kann eine Kachel entscheiden, ob sie kompakt bleibt oder die
     * Fläche ausnutzt – der Browser allein weiß das nicht zuverlässig, weil
     * die Zeilenhöhe sonst vom Inhalt abhinge und sich beides gegenseitig
     * hochschaukelt.
     */
    #[Locked]
    public int $cols = 2;

    #[Locked]
    public int $rows = 1;

    public function mount(string $class = '', string $variant = 'tile', int $cols = 2, int $rows = 1): void
    {
        $this->class = $class;

        // Der Wert landet im Ansichtsnamen, deshalb nur Bekanntes durchlassen.
        $this->variant = in_array($variant, self::VARIANTS, true) ? $variant : 'tile';

        $this->cols = max(1, min(6, $cols));
        $this->rows = max(1, min(4, $rows));
    }

    /** Genug Fläche für eine großzügige Darstellung? */
    public function roomy(): bool
    {
        return $this->rows >= 2 && $this->cols >= 3;
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
