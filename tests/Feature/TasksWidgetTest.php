<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Tasks;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-09 10:00', 'Europe/Berlin'));
    }

    public function test_it_shows_an_empty_list(): void
    {
        Livewire::test(Tasks::class)->assertSee('Nichts zu tun');
    }

    public function test_it_adds_a_task(): void
    {
        Livewire::test(Tasks::class)
            ->set('newTask', 'Müll rausbringen')
            ->call('add')
            ->assertSet('newTask', '')
            ->assertSee('Müll rausbringen');

        $this->assertDatabaseHas('tasks', ['name' => 'Müll rausbringen', 'due_on' => null]);
    }

    public function test_it_adds_a_task_with_a_due_date(): void
    {
        Livewire::test(Tasks::class, ['variant' => 'page'])
            ->set('newTask', 'Rechnung zahlen')
            ->set('newDueOn', '2026-09-12')
            ->call('add')
            ->assertSet('newDueOn', '');

        $this->assertSame('2026-09-12', Task::first()->due_on->toDateString());
    }

    public function test_it_refuses_an_empty_task(): void
    {
        Livewire::test(Tasks::class)
            ->set('newTask', '')
            ->call('add')
            ->assertHasErrors(['newTask' => 'required']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_refuses_an_unreadable_date(): void
    {
        Livewire::test(Tasks::class)
            ->set('newTask', 'Irgendwas')
            ->set('newDueOn', 'übermorgen vielleicht')
            ->call('add')
            ->assertHasErrors(['newDueOn' => 'date']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_it_ticks_a_task_off_and_back_on(): void
    {
        $task = Task::factory()->create();

        Livewire::test(Tasks::class)->call('toggle', $task->id);
        $this->assertNotNull($task->fresh()->completed_at);

        Livewire::test(Tasks::class)->call('toggle', $task->id);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_it_removes_and_clears_tasks(): void
    {
        $task = Task::factory()->create(['name' => 'Weg damit']);
        Task::factory()->done()->create(['name' => 'Schon erledigt']);
        Task::factory()->create(['name' => 'Bleibt offen']);

        Livewire::test(Tasks::class)->call('remove', $task->id);
        $this->assertDatabaseMissing('tasks', ['name' => 'Weg damit']);

        Livewire::test(Tasks::class)->call('clearCompleted');
        $this->assertDatabaseMissing('tasks', ['name' => 'Schon erledigt']);
        $this->assertDatabaseHas('tasks', ['name' => 'Bleibt offen']);
    }

    public function test_due_dates_are_named_in_words(): void
    {
        $this->assertSame('Heute', Task::factory()->create(['due_on' => '2026-09-09'])->dueLabel());
        $this->assertSame('Morgen', Task::factory()->create(['due_on' => '2026-09-10'])->dueLabel());
        $this->assertSame('Überfällig', Task::factory()->create(['due_on' => '2026-09-01'])->dueLabel());
        $this->assertSame('Fr 11.9.', Task::factory()->create(['due_on' => '2026-09-11'])->dueLabel());
        $this->assertNull(Task::factory()->create(['due_on' => null])->dueLabel());
    }

    public function test_today_and_overdue_count_as_urgent(): void
    {
        $this->assertTrue(Task::factory()->create(['due_on' => '2026-09-09'])->isUrgent());
        $this->assertTrue(Task::factory()->create(['due_on' => '2026-09-01'])->isUrgent());
        $this->assertFalse(Task::factory()->create(['due_on' => '2026-09-20'])->isUrgent());
        $this->assertFalse(Task::factory()->create(['due_on' => null])->isUrgent());

        // Erledigtes drängt nicht mehr, auch wenn das Datum vorbei ist.
        $this->assertFalse(Task::factory()->done()->create(['due_on' => '2026-09-01'])->isUrgent());
    }

    public function test_the_soonest_task_comes_first_and_undated_ones_last(): void
    {
        Task::factory()->create(['name' => 'Ohne Datum']);
        Task::factory()->create(['name' => 'Naechste Woche', 'due_on' => '2026-09-16']);
        Task::factory()->create(['name' => 'Heute faellig', 'due_on' => '2026-09-09']);
        Task::factory()->done()->create(['name' => 'Erledigt']);

        $html = Livewire::test(Tasks::class)->html();
        $order = fn (string $name) => strpos($html, 'data-task="'.$name.'"');

        $this->assertLessThan($order('Naechste Woche'), $order('Heute faellig'));
        $this->assertLessThan($order('Ohne Datum'), $order('Naechste Woche'));
        $this->assertLessThan($order('Erledigt'), $order('Ohne Datum'));
    }

    public function test_the_page_offers_a_date_field_the_tile_does_not(): void
    {
        Livewire::test(Tasks::class, ['variant' => 'page'])->assertSeeHtml('type="date"');
        Livewire::test(Tasks::class, ['variant' => 'tile'])->assertDontSeeHtml('type="date"');
    }

    public function test_an_unknown_variant_falls_back_to_the_tile(): void
    {
        Livewire::test(Tasks::class, ['variant' => '../../etc/passwd'])
            ->assertOk()
            ->assertSet('variant', 'tile');
    }
}
