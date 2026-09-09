<?php

namespace App\Services\Wifi;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;

/**
 * Erzeugt den WLAN-QR-Code als eingebettetes SVG.
 *
 * SVG statt PNG, weil dafür keine Bildbibliothek nötig ist und der Code auf
 * dem iPad in jeder Größe scharf bleibt.
 */
final class WifiQrCode
{
    /** Ruhezone in Modulen. Vier ist der Standard und sichert das Einlesen. */
    private const QUIET_ZONE = 4;

    private const SIZE = 400;

    public function svg(WifiNetwork $network): ?string
    {
        if (! $network->isConfigured()) {
            return null;
        }

        $uri = $network->toUri();

        // Der Code ändert sich nur, wenn sich die Zugangsdaten ändern.
        return Cache::remember(
            'dashboard.wifi.'.md5($uri),
            now()->addDay(),
            fn () => $this->render($uri),
        );
    }

    private function render(string $uri): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle(self::SIZE, self::QUIET_ZONE),
            new SvgImageBackEnd(),
        ));

        // Die XML-Deklaration muss weg, sonst landet sie mitten im HTML.
        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $writer->writeString($uri));
    }
}
