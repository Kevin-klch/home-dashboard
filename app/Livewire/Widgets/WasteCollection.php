<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Services\Calendar\CalendarEvent;
use App\Services\Calendar\CalendarSources;
use App\Services\Calendar\WasteType;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Livewire\Component;

class WasteCollection extends Component
{
    use RendersAsTileOrPage;

    public function render(CalendarSources $sources): View
    {
        $calendar = $sources->waste();
        $events = $calendar->upcoming();

        return view($this->variantView('waste'), [
            'configured' => $calendar->isConfigured(),
            'unreachable' => $events === null,
            'days' => $this->groupByDay($events ?? []),
        ]);
    }

    /**
     * Mehrere Tonnen am selben Tag gehören in eine Zeile – sonst steht
     * derselbe Termin dreimal untereinander.
     *
     * @param  list<CalendarEvent>  $events
     * @return list<array{date: CarbonImmutable, label: string, inDays: int, when: string, types: list<WasteType>}>
     */
    private function groupByDay(array $events): array
    {
        $today = CarbonImmutable::now(config('dashboard.waste.timezone'))->startOfDay();
        $days = [];

        foreach ($events as $event) {
            $key = $event->dayKey();

            if (! isset($days[$key])) {
                $inDays = (int) $today->diffInDays($event->start->startOfDay());

                $days[$key] = [
                    'date' => $event->start,
                    'label' => $event->dayLabel(),
                    'inDays' => $inDays,
                    'when' => $this->whenLabel($inDays),
                    'types' => [],
                ];
            }

            $type = WasteType::fromTitle($event->title);

            // Dieselbe Tonne am selben Tag nur einmal nennen.
            if (! in_array($type, $days[$key]['types'], true)) {
                $days[$key]['types'][] = $type;
            }
        }

        return array_values($days);
    }

    private function whenLabel(int $inDays): string
    {
        return match ($inDays) {
            0 => 'heute',
            1 => 'morgen',
            2 => 'übermorgen',
            default => "in {$inDays} Tagen",
        };
    }
}
