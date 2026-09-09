<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Models\Task;
use Illuminate\View\View;
use Livewire\Component;

class Tasks extends Component
{
    use RendersAsTileOrPage;

    public string $newTask = '';

    /** Optionales Fälligkeitsdatum, nur auf der Seite eingebbar. */
    public string $newDueOn = '';

    public function add(): void
    {
        $this->validate(
            [
                'newTask' => ['required', 'string', 'max:100'],
                'newDueOn' => ['nullable', 'date'],
            ],
            [
                'newTask.required' => 'Bitte etwas eintragen.',
                'newTask.max' => 'Höchstens 100 Zeichen.',
                'newDueOn.date' => 'Das Datum ist unlesbar.',
            ],
        );

        Task::create([
            'name' => trim($this->newTask),
            'due_on' => $this->newDueOn !== '' ? $this->newDueOn : null,
        ]);

        $this->newTask = '';
        $this->newDueOn = '';
    }

    public function toggle(int $id): void
    {
        Task::query()->find($id)?->toggle();
    }

    public function remove(int $id): void
    {
        Task::query()->find($id)?->delete();
    }

    public function clearCompleted(): void
    {
        Task::query()->done()->delete();
    }

    public function render(): View
    {
        $tasks = Task::query()->inTaskOrder()->get();
        $done = $tasks->whereNotNull('completed_at')->count();

        return view($this->variantView('tasks'), [
            'tasks' => $tasks,
            'openCount' => $tasks->count() - $done,
            'doneCount' => $done,
            // Anteil erledigter Aufgaben, für den Balken.
            'progress' => $tasks->isEmpty() ? 0 : (int) round($done / $tasks->count() * 100),
        ]);
    }
}
