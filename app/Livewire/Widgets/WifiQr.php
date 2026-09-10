<?php

namespace App\Livewire\Widgets;

use App\Livewire\Concerns\RendersAsTileOrPage;
use App\Services\Wifi\WifiNetwork;
use App\Services\Wifi\WifiQrCode;
use Illuminate\View\View;
use Livewire\Component;

class WifiQr extends Component
{
    use RendersAsTileOrPage;

    public function render(WifiQrCode $qr): View
    {
        $network = WifiNetwork::fromConfig();

        return view($this->variantView('wifi-qr'), [
            'network' => $network,
            // null heißt: keine Zugangsdaten hinterlegt.
            'svg' => $qr->svg($network),
        ]);
    }
}
