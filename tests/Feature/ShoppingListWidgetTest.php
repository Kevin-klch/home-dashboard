<?php

namespace Tests\Feature;

use App\Livewire\Widgets\ShoppingList;
use App\Models\ShoppingItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ShoppingListWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_an_empty_list(): void
    {
        Livewire::test(ShoppingList::class)->assertSee('Liste ist leer');
    }

    public function test_it_adds_an_item(): void
    {
        Livewire::test(ShoppingList::class)
            ->set('newItem', 'Milch')
            ->call('add')
            ->assertSet('newItem', '')
            ->assertSee('Milch');

        $this->assertDatabaseHas('shopping_items', ['name' => 'Milch', 'completed_at' => null]);
    }

    public function test_it_trims_whitespace(): void
    {
        Livewire::test(ShoppingList::class)
            ->set('newItem', '   Brot   ')
            ->call('add');

        $this->assertDatabaseHas('shopping_items', ['name' => 'Brot']);
    }

    public function test_it_refuses_an_empty_entry(): void
    {
        Livewire::test(ShoppingList::class)
            ->set('newItem', '')
            ->call('add')
            ->assertHasErrors(['newItem' => 'required']);

        $this->assertDatabaseCount('shopping_items', 0);
    }

    public function test_it_refuses_an_overly_long_entry(): void
    {
        Livewire::test(ShoppingList::class)
            ->set('newItem', str_repeat('a', 101))
            ->call('add')
            ->assertHasErrors(['newItem' => 'max']);

        $this->assertDatabaseCount('shopping_items', 0);
    }

    public function test_it_does_not_add_the_same_open_item_twice(): void
    {
        ShoppingItem::factory()->create(['name' => 'Milch']);

        Livewire::test(ShoppingList::class)
            ->set('newItem', 'milch')   // andere Schreibweise, gleiche Sache
            ->call('add')
            ->assertSee('steht schon auf der Liste');

        $this->assertDatabaseCount('shopping_items', 1);
    }

    public function test_an_already_bought_item_can_be_added_again(): void
    {
        // Milch von letzter Woche ist abgehakt – diese Woche darf sie wieder drauf.
        ShoppingItem::factory()->done()->create(['name' => 'Milch']);

        Livewire::test(ShoppingList::class)
            ->set('newItem', 'Milch')
            ->call('add');

        $this->assertDatabaseCount('shopping_items', 2);
    }

    public function test_it_ticks_an_item_off_and_back_on(): void
    {
        $item = ShoppingItem::factory()->create(['name' => 'Tomaten']);

        Livewire::test(ShoppingList::class)->call('toggle', $item->id);
        $this->assertNotNull($item->fresh()->completed_at);

        Livewire::test(ShoppingList::class)->call('toggle', $item->id);
        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_it_removes_an_item(): void
    {
        $item = ShoppingItem::factory()->create(['name' => 'Kaffee']);

        Livewire::test(ShoppingList::class)
            ->call('remove', $item->id)
            ->assertDontSee('Kaffee');

        $this->assertDatabaseCount('shopping_items', 0);
    }

    public function test_it_clears_only_the_completed_items(): void
    {
        ShoppingItem::factory()->create(['name' => 'Offen']);
        ShoppingItem::factory()->done()->create(['name' => 'Erledigt']);

        Livewire::test(ShoppingList::class)->call('clearCompleted');

        $this->assertDatabaseHas('shopping_items', ['name' => 'Offen']);
        $this->assertDatabaseMissing('shopping_items', ['name' => 'Erledigt']);
    }

    public function test_open_items_come_before_completed_ones(): void
    {
        // Bewusst zuerst das erledigte anlegen, damit die Sortierung wirkt.
        ShoppingItem::factory()->done()->create(['name' => 'Olivenoel']);
        ShoppingItem::factory()->create(['name' => 'Zwiebeln']);

        $html = Livewire::test(ShoppingList::class)->html();

        $this->assertLessThan(
            strpos($html, 'data-item="Olivenoel"'),
            strpos($html, 'data-item="Zwiebeln"'),
        );
    }

    public function test_it_counts_open_and_completed_items(): void
    {
        ShoppingItem::factory()->count(3)->create();
        ShoppingItem::factory()->done()->count(2)->create();

        Livewire::test(ShoppingList::class)
            ->assertSee('3 Einträge')
            ->assertSee('2 erledigte Einträge entfernen');
    }

    public function test_it_uses_the_singular_for_a_single_item(): void
    {
        ShoppingItem::factory()->create();
        ShoppingItem::factory()->done()->create();

        Livewire::test(ShoppingList::class)
            ->assertSee('1 Eintrag')
            ->assertSee('1 erledigten Eintrag entfernen');
    }

    public function test_the_list_is_shared_and_not_tied_to_a_user(): void
    {
        // Der Haushalt teilt sich eine Liste – ein Benutzerbezug wäre falsch.
        $this->assertFalse(
            Schema::hasColumn('shopping_items', 'user_id')
        );
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        Livewire::test(ShoppingList::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }
}
