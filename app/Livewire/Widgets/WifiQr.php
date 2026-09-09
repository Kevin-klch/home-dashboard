<?php

namespace App\Livewire\Widgets;

use App\Services\Wifi\WifiNetwork;
use App\Services\Wifi\WifiQrCode;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WifiQr extends Component
{
    // Reines Layout-Detail vom Dashboard – darf vom Client nicht geändert werden.
    #[Locked]
    public string $class = '';

    public function mount(string $class = ''): void
    {
        $this->class = $class;
    }

    public function render(WifiQrCode $qr): View
    {
        $network = WifiNetwork::fromConfig();

        return view('livewire.widgets.wifi-qr', [
            'network' => $network,
            // null heißt: keine Zugangsdaten hinterlegt.
            'svg' => $qr->svg($network),
        ]);
    }
}
