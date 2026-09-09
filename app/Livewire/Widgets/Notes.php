<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Models\Note;
use Illuminate\View\View;
use Livewire\Component;

class Notes extends Component
{
    use RendersAsTileOrPage;

    public string $newNote = '';

    /** Notiz, die gerade bearbeitet wird. */
    public ?int $editingId = null;

    public string $editingBody = '';

    public function add(): void
    {
        $this->validate(
            ['newNote' => ['required', 'string', 'max:500']],
            [
                'newNote.required' => 'Bitte etwas eintragen.',
                'newNote.max' => 'Höchstens 500 Zeichen.',
            ],
        );

        Note::create(['body' => trim($this->newNote)]);

        $this->newNote = '';
    }

    public function edit(int $id): void
    {
        $note = Note::query()->find($id);

        if ($note === null) {
            return;
        }

        $this->editingId = $note->id;
        $this->editingBody = $note->body;
    }

    public function save(): void
    {
        $this->validate(
            ['editingBody' => ['required', 'string', 'max:500']],
            [
                'editingBody.required' => 'Die Notiz darf nicht leer sein.',
                'editingBody.max' => 'Höchstens 500 Zeichen.',
            ],
        );

        Note::query()->find($this->editingId)?->update(['body' => trim($this->editingBody)]);

        $this->cancel();
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->editingBody = '';
        $this->resetValidation();
    }

    public function togglePin(int $id): void
    {
        Note::query()->find($id)?->togglePin();
    }

    public function remove(int $id): void
    {
        Note::query()->find($id)?->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }
    }

    public function render(): View
    {
        // Auf dem Dashboard reichen die neuesten paar, die Seite zeigt alles.
        $query = Note::query()->inNoteOrder();

        return view($this->variantView('notes'), [
            'notes' => $this->isPage() ? $query->get() : $query->limit(4)->get(),
            'total' => Note::query()->count(),
        ]);
    }
}
