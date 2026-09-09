<?php

namespace App\Livewire\Widgets;

use App\Services\Calendar\BirthdayName;
use App\Services\Calendar\CalendarEvent;
use App\Services\Calendar\CalendarSources;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Birthdays extends Component
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
        $calendar = $sources->birthdays();
        $events = $calendar->upcoming();

        return view('livewire.widgets.birthdays', [
            'configured' => $calendar->isConfigured(),
            'unreachable' => $events === null,
            ...$this->split($events ?? []),
        ]);
    }

    /**
     * Teilt die Geburtstage in "heute" und "als nächstes".
     *
     * @param  list<CalendarEvent>  $events
     * @return array{today: list<string>, upcoming: list<array{name: string, date: CarbonImmutable, inDays: int, inLabel: string}>}
     */
    private function split(array $events): array
    {
        $today = CarbonImmutable::now(config('dashboard.birthdays.timezone'))->startOfDay();

        $celebrating = [];
        $upcoming = [];

        foreach ($events as $event) {
            $name = BirthdayName::from($event->title);

            if ($event->start->isSameDay($today)) {
                $celebrating[] = $name;

                continue;
            }

            // diffInDays auf Tagesgrenzen, damit "morgen" auch wirklich 1 ergibt.
            $inDays = (int) $today->diffInDays($event->start->startOfDay());

            $upcoming[] = [
                'name' => $name,
                'date' => $event->start,
                'inDays' => $inDays,
                'inLabel' => $inDays === 1 ? 'morgen' : "in {$inDays} Tagen",
            ];
        }

        return ['today' => $celebrating, 'upcoming' => $upcoming];
    }
}
