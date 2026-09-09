<?php

namespace App\Livewire\Widgets;

use App\Services\Calendar\CalendarSources;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Calendar extends Component
{
    // Reines Layout-Detail vom Dashboard – darf vom Client nicht geändert werden.
    #[Locked]
    public string $class = '';

    public function mount(string $class = ''): void
    {
        $this->class = $class;
    }

    public function render(CalendarSources $sources): View
    {
        $calendar = $sources->appointments();

        return view('livewire.widgets.calendar', [
            // null heißt: keine Quelle eingerichtet oder gerade nicht erreichbar.
            'events' => $calendar->upcoming(),
            'configured' => $calendar->isConfigured(),
        ]);
    }
}
