<?php

namespace App\Livewire\Widgets;

use App\Services\Weather\WeatherProvider;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Weather extends Component
{
    // Reines Layout-Detail vom Dashboard – darf vom Client nicht geändert werden.
    #[Locked]
    public string $class = '';

    public function mount(string $class = ''): void
    {
        $this->class = $class;
    }

    public function render(WeatherProvider $weather): View
    {
        return view('livewire.widgets.weather', [
            // Kann null sein, wenn die Wetterquelle gerade nicht erreichbar ist.
            'snapshot' => $weather->current(),
        ]);
    }
}
