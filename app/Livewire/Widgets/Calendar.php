<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Services\Calendar\CalendarSources;
use Illuminate\View\View;
use Livewire\Component;

class Calendar extends Component
{
    use RendersAsTileOrPage;

    /** Die Seite schaut weiter voraus als die Kachel. */
    private const PAGE_DAYS = 30;

    private const PAGE_EVENTS = 40;

    public function render(CalendarSources $sources): View
    {
        $calendar = $this->isPage()
            ? $sources->appointments(self::PAGE_DAYS, self::PAGE_EVENTS)
            : $sources->appointments();

        return view($this->variantView('calendar'), [
            // null heißt: keine Quelle eingerichtet oder gerade nicht erreichbar.
            'events' => $calendar->upcoming(),
            'configured' => $calendar->isConfigured(),
            'daysAhead' => $this->isPage() ? self::PAGE_DAYS : config('dashboard.calendar.days_ahead'),
        ]);
    }
}
