<?php

namespace Tests\Feature;

use App\Dashboard\WidgetRegistry;
use App\Livewire\DashboardGrid;
use App\Models\DashboardWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardGridTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDashboardSources();
    }

    private function order(): array
    {
        return DashboardWidget::query()->inOrder()->pluck('widget')->all();
    }

    public function test_the_default_layout_is_created_on_first_use(): void
    {
        $this->assertDatabaseCount('dashboard_widgets', 0);

        Livewire::test(DashboardGrid::class);

        $expected = array_keys((new WidgetRegistry)->defaults());

        $this->assertSame($expected, $this->order());
    }

    public function test_it_does_not_seed_twice(): void
    {
        Livewire::test(DashboardGrid::class);
        $before = DashboardWidget::query()->count();

        Livewire::test(DashboardGrid::class);

        $this->assertSame($before, DashboardWidget::query()->count());
    }

    public function test_a_tile_moves_forward_and_back(): void
    {
        Livewire::test(DashboardGrid::class);
        $original = $this->order();

        Livewire::test(DashboardGrid::class)->call('moveLater', $original[0]);

        $moved = $this->order();
        $this->assertSame($original[1], $moved[0]);
        $this->assertSame($original[0], $moved[1]);

        Livewire::test(DashboardGrid::class)->call('moveEarlier', $original[0]);

        $this->assertSame($original, $this->order());
    }

    public function test_the_first_tile_cannot_move_further_forward(): void
    {
        Livewire::test(DashboardGrid::class);
        $original = $this->order();

        Livewire::test(DashboardGrid::class)->call('moveEarlier', $original[0]);

        $this->assertSame($original, $this->order());
    }

    public function test_the_last_tile_cannot_move_further_back(): void
    {
        Livewire::test(DashboardGrid::class);
        $original = $this->order();

        $last = end($original);
        Livewire::test(DashboardGrid::class)->call('moveLater', $last);

        $this->assertSame($original, $this->order());
    }

    public function test_a_tile_gets_wider_and_narrower(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('widen', 'calendar');
        $this->assertSame(3, DashboardWidget::query()->where('widget', 'calendar')->value('width'));

        Livewire::test(DashboardGrid::class)->call('narrow', 'calendar');
        $this->assertSame(2, DashboardWidget::query()->where('widget', 'calendar')->value('width'));
    }

    public function test_the_weather_tile_refuses_to_get_too_narrow(): void
    {
        // Schmaler als vier Spalten passen Stundenleiste und Wochenspalte nicht.
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('narrow', 'weather');

        $this->assertSame(4, DashboardWidget::query()->where('widget', 'weather')->value('width'));
    }

    public function test_widths_stop_at_the_upper_end(): void
    {
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'calendar')->update(['width' => 4]);

        Livewire::test(DashboardGrid::class)->call('widen', 'calendar');

        $this->assertSame(4, DashboardWidget::query()->where('widget', 'calendar')->value('width'));
    }

    public function test_an_impossible_width_snaps_back_to_something_allowed(): void
    {
        // Etwa nachdem die erlaubten Breiten einer Kachel geändert wurden.
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'weather')->update(['width' => 5]);

        Livewire::test(DashboardGrid::class)->call('narrow', 'weather');

        $this->assertContains(
            DashboardWidget::query()->where('widget', 'weather')->value('width'),
            (new WidgetRegistry)->widths('weather')
        );
    }

    public function test_a_tile_can_be_removed_and_added_again(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('remove', 'music');
        $this->assertNotContains('music', $this->order());

        Livewire::test(DashboardGrid::class)->call('add', 'music');
        $this->assertContains('music', $this->order());

        // Wieder hinzugefügte Kacheln landen am Ende.
        $order = $this->order();
        $this->assertSame('music', end($order));
    }

    public function test_removing_closes_the_gaps_in_the_positions(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('remove', 'calendar');

        $positions = DashboardWidget::query()->inOrder()->pluck('position')->all();

        $this->assertSame(range(0, count($positions) - 1), $positions);
    }

    public function test_adding_an_unknown_widget_does_nothing(): void
    {
        Livewire::test(DashboardGrid::class);
        $before = $this->order();

        Livewire::test(DashboardGrid::class)->call('add', 'gibtesnicht');

        $this->assertSame($before, $this->order());
    }

    public function test_a_widget_cannot_be_placed_twice(): void
    {
        Livewire::test(DashboardGrid::class);
        $before = $this->order();

        Livewire::test(DashboardGrid::class)->call('add', 'calendar');

        $this->assertSame($before, $this->order());
    }

    public function test_a_widget_that_no_longer_exists_is_skipped(): void
    {
        // Eine umbenannte Kachel darf nicht das ganze Dashboard lahmlegen.
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->create(['widget' => 'alteKachel', 'position' => 99, 'width' => 2]);

        Livewire::test(DashboardGrid::class)
            ->assertOk()
            ->assertDontSeeHtml('data-tile="alteKachel"');
    }

    public function test_the_layout_can_be_reset(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)
            ->call('remove', 'music')
            ->call('widen', 'calendar')
            ->call('resetLayout');

        $this->assertSame(array_keys((new WidgetRegistry)->defaults()), $this->order());
        $this->assertSame(2, DashboardWidget::query()->where('widget', 'calendar')->value('width'));
    }

    public function test_the_controls_appear_only_while_arranging(): void
    {
        Livewire::test(DashboardGrid::class)
            ->assertDontSeeHtml('data-move-later=')
            ->assertDontSee('Verfügbare Kacheln')
            ->call('toggleArranging')
            ->assertSet('arranging', true)
            ->assertSeeHtml('data-move-later=')
            ->assertSee('Verfügbare Kacheln');
    }

    public function test_unplaced_widgets_are_offered_for_adding(): void
    {
        Livewire::test(DashboardGrid::class)
            ->call('toggleArranging')
            // Notizen und WLAN liegen standardmäßig nicht auf dem Dashboard.
            ->assertSeeHtml('data-add="notes"')
            ->assertSeeHtml('data-add="wifi"')
            ->assertDontSeeHtml('data-add="calendar"');
    }

    public function test_the_width_reaches_the_markup(): void
    {
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'notes')->delete();
        DashboardWidget::query()->create(['widget' => 'notes', 'position' => 99, 'width' => 6]);

        Livewire::test(DashboardGrid::class)->assertSeeHtml('col-span-6');
    }

    // ------------------------------------------------------------------
    // Höhe
    // ------------------------------------------------------------------

    public function test_a_tile_gets_taller_and_shorter(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('taller', 'shopping');
        $this->assertSame(2, DashboardWidget::query()->where('widget', 'shopping')->value('height'));

        Livewire::test(DashboardGrid::class)->call('shorter', 'shopping');
        $this->assertSame(1, DashboardWidget::query()->where('widget', 'shopping')->value('height'));
    }

    public function test_a_tile_cannot_get_shorter_than_one_row(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)->call('shorter', 'calendar');

        $this->assertSame(1, DashboardWidget::query()->where('widget', 'calendar')->value('height'));
    }

    public function test_heights_stop_at_the_upper_end(): void
    {
        // Die Wetterkachel darf höchstens zwei Zeilen hoch werden.
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'weather')->update(['height' => 2]);

        Livewire::test(DashboardGrid::class)->call('taller', 'weather');

        $this->assertSame(2, DashboardWidget::query()->where('widget', 'weather')->value('height'));
    }

    public function test_lists_may_grow_to_three_rows(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)
            ->call('taller', 'tasks')
            ->call('taller', 'tasks');

        $this->assertSame(3, DashboardWidget::query()->where('widget', 'tasks')->value('height'));
    }

    public function test_an_impossible_height_snaps_back_to_something_allowed(): void
    {
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'weather')->update(['height' => 7]);

        Livewire::test(DashboardGrid::class)->call('shorter', 'weather');

        $this->assertContains(
            DashboardWidget::query()->where('widget', 'weather')->value('height'),
            (new WidgetRegistry)->heights('weather')
        );
    }

    public function test_the_height_reaches_the_markup(): void
    {
        Livewire::test(DashboardGrid::class);
        DashboardWidget::query()->where('widget', 'tasks')->update(['height' => 3]);

        Livewire::test(DashboardGrid::class)->assertSeeHtml('row-span-3');
    }

    public function test_the_grid_gives_its_rows_a_base_height(): void
    {
        // Ohne feste Grundhöhe wäre eine Kachel über zwei Zeilen nicht
        // vorhersagbar hoch – die Zeilen richten sich sonst nach dem Inhalt.
        Livewire::test(DashboardGrid::class)->assertSeeHtml('auto-rows-[minmax(12rem,auto)]');
    }

    public function test_height_controls_appear_while_arranging(): void
    {
        Livewire::test(DashboardGrid::class)
            ->assertDontSeeHtml('data-taller=')
            ->call('toggleArranging')
            ->assertSeeHtml('data-taller=')
            ->assertSeeHtml('data-shorter=')
            ->assertSee('Höhe');
    }

    public function test_resetting_also_restores_the_heights(): void
    {
        Livewire::test(DashboardGrid::class);

        Livewire::test(DashboardGrid::class)
            ->call('taller', 'tasks')
            ->call('resetLayout');

        $this->assertSame(1, DashboardWidget::query()->where('widget', 'tasks')->value('height'));
    }
}
