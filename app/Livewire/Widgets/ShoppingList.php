<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Models\ShoppingItem;
use Illuminate\View\View;
use Livewire\Component;

class ShoppingList extends Component
{
    use RendersAsTileOrPage;

    public string $newItem = '';

    /** Kurze Rückmeldung, etwa bei einem doppelten Eintrag. */
    public ?string $notice = null;

    public function add(): void
    {
        // Meldungen inline, weil es keine deutschen Sprachdateien im Projekt gibt.
        $this->validate(
            ['newItem' => ['required', 'string', 'max:100']],
            [
                'newItem.required' => 'Bitte etwas eintragen.',
                'newItem.max' => 'Höchstens 100 Zeichen.',
            ],
        );

        $name = trim($this->newItem);

        // Steht es schon offen auf der Liste, wäre ein zweiter Eintrag nur
        // lästig – im Laden übersieht man den doppelten Posten ohnehin.
        $existing = ShoppingItem::query()
            ->open()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing !== null) {
            $this->notice = $existing->name.' steht schon auf der Liste.';
            $this->newItem = '';

            return;
        }

        ShoppingItem::create(['name' => $name]);

        $this->newItem = '';
        $this->notice = null;
    }

    public function toggle(int $id): void
    {
        ShoppingItem::query()->find($id)?->toggle();

        $this->notice = null;
    }

    public function remove(int $id): void
    {
        ShoppingItem::query()->find($id)?->delete();

        $this->notice = null;
    }

    public function clearCompleted(): void
    {
        ShoppingItem::query()->done()->delete();

        $this->notice = null;
    }

    public function render(): View
    {
        $items = ShoppingItem::query()->inShoppingOrder()->get();

        return view($this->variantView('shopping-list'), [
            'items' => $items,
            'openCount' => $items->whereNull('completed_at')->count(),
            'doneCount' => $items->whereNotNull('completed_at')->count(),
        ]);
    }
}
