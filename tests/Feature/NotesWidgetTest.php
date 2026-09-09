<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Notes;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotesWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_an_empty_state(): void
    {
        Livewire::test(Notes::class)->assertSee('Keine Notizen');
        Livewire::test(Notes::class, ['variant' => 'page'])->assertSee('Noch keine Notizen');
    }

    public function test_it_adds_a_note(): void
    {
        Livewire::test(Notes::class)
            ->set('newNote', 'Ersatzschlüssel bei Familie Weber')
            ->call('add')
            ->assertSet('newNote', '')
            ->assertSee('Ersatzschlüssel bei Familie Weber');

        $this->assertDatabaseCount('notes', 1);
    }

    public function test_it_refuses_an_empty_note(): void
    {
        Livewire::test(Notes::class)
            ->set('newNote', '')
            ->call('add')
            ->assertHasErrors(['newNote' => 'required']);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_it_refuses_an_overly_long_note(): void
    {
        Livewire::test(Notes::class)
            ->set('newNote', str_repeat('a', 501))
            ->call('add')
            ->assertHasErrors(['newNote' => 'max']);
    }

    public function test_it_edits_a_note(): void
    {
        $note = Note::factory()->create(['body' => 'Alter Text']);

        Livewire::test(Notes::class, ['variant' => 'page'])
            ->call('edit', $note->id)
            ->assertSet('editingId', $note->id)
            ->assertSet('editingBody', 'Alter Text')
            ->set('editingBody', 'Neuer Text')
            ->call('save')
            ->assertSet('editingId', null);

        $this->assertSame('Neuer Text', $note->fresh()->body);
    }

    public function test_cancelling_keeps_the_original_text(): void
    {
        $note = Note::factory()->create(['body' => 'Original']);

        Livewire::test(Notes::class, ['variant' => 'page'])
            ->call('edit', $note->id)
            ->set('editingBody', 'Verworfen')
            ->call('cancel')
            ->assertSet('editingId', null);

        $this->assertSame('Original', $note->fresh()->body);
    }

    public function test_it_refuses_to_save_an_empty_note(): void
    {
        $note = Note::factory()->create(['body' => 'Bleibt so']);

        Livewire::test(Notes::class, ['variant' => 'page'])
            ->call('edit', $note->id)
            ->set('editingBody', '')
            ->call('save')
            ->assertHasErrors(['editingBody' => 'required']);

        $this->assertSame('Bleibt so', $note->fresh()->body);
    }

    public function test_it_pins_and_unpins(): void
    {
        $note = Note::factory()->create();

        Livewire::test(Notes::class)->call('togglePin', $note->id);
        $this->assertNotNull($note->fresh()->pinned_at);

        Livewire::test(Notes::class)->call('togglePin', $note->id);
        $this->assertNull($note->fresh()->pinned_at);
    }

    public function test_pinned_notes_come_first(): void
    {
        Note::factory()->create(['body' => 'Gewoehnlich', 'updated_at' => now()]);
        Note::factory()->pinned()->create(['body' => 'Angeheftet', 'updated_at' => now()->subDay()]);

        $html = Livewire::test(Notes::class, ['variant' => 'page'])->html();

        $this->assertLessThan(
            strpos($html, 'Gewoehnlich'),
            strpos($html, 'Angeheftet'),
        );
    }

    public function test_it_removes_a_note(): void
    {
        $note = Note::factory()->create(['body' => 'Weg damit']);

        Livewire::test(Notes::class)
            ->call('remove', $note->id)
            ->assertDontSee('Weg damit');

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_removing_the_note_being_edited_closes_the_editor(): void
    {
        $note = Note::factory()->create();

        Livewire::test(Notes::class, ['variant' => 'page'])
            ->call('edit', $note->id)
            ->call('remove', $note->id)
            ->assertSet('editingId', null);
    }

    public function test_the_tile_shows_only_the_latest_few(): void
    {
        Note::factory()->count(7)->create();

        $tile = Livewire::test(Notes::class)->html();
        $page = Livewire::test(Notes::class, ['variant' => 'page'])->html();

        $this->assertSame(4, substr_count($tile, 'data-note='));
        $this->assertSame(7, substr_count($page, 'data-note='));
    }

    public function test_the_tile_reports_the_total(): void
    {
        Note::factory()->count(6)->create();

        Livewire::test(Notes::class)->assertSee('6 gesamt');
    }
}
