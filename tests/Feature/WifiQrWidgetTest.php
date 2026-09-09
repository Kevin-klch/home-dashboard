<?php

namespace Tests\Feature;

use App\Livewire\Widgets\WifiQr;
use App\Services\Wifi\WifiNetwork;
use App\Services\Wifi\WifiQrCode;
use Livewire\Livewire;
use Tests\TestCase;

class WifiQrWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dashboard.wifi.ssid' => 'Kevins WLAN',
            'dashboard.wifi.password' => 'geheim123',
            'dashboard.wifi.encryption' => 'WPA',
            'dashboard.wifi.hidden' => false,
        ]);
    }

    public function test_it_renders_a_qr_code(): void
    {
        $html = Livewire::test(WifiQr::class)->html();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('data-qr', $html);
        $this->assertStringContainsString('Kevins WLAN', $html);
    }

    public function test_the_svg_carries_no_xml_declaration(): void
    {
        // Eine Deklaration mitten im HTML würde die Seite zerlegen.
        $html = Livewire::test(WifiQr::class)->html();

        $this->assertStringNotContainsString('<?xml', $html);
    }

    public function test_the_code_is_derived_from_the_credentials(): void
    {
        $qr = app(WifiQrCode::class);

        $first = $qr->svg(WifiNetwork::fromConfig());

        config(['dashboard.wifi.password' => 'ein anderes Passwort']);
        $second = $qr->svg(WifiNetwork::fromConfig());

        $this->assertNotNull($first);
        $this->assertNotSame($first, $second);
    }

    public function test_the_same_credentials_come_from_the_cache(): void
    {
        $qr = app(WifiQrCode::class);
        $network = WifiNetwork::fromConfig();

        $this->assertSame($qr->svg($network), $qr->svg($network));
    }

    public function test_it_names_the_encryption(): void
    {
        Livewire::test(WifiQr::class)->assertSee('WPA/WPA2');
    }

    public function test_it_marks_a_hidden_network(): void
    {
        config(['dashboard.wifi.hidden' => true]);

        Livewire::test(WifiQr::class)->assertSee('verstecktes Netz');
    }

    public function test_it_asks_for_setup_without_a_network_name(): void
    {
        config(['dashboard.wifi.ssid' => null]);

        Livewire::test(WifiQr::class)
            ->assertSee('WLAN noch nicht hinterlegt')
            ->assertSee('WIFI_SSID')
            ->assertDontSeeHtml('<svg viewBox');
    }

    public function test_it_asks_for_setup_when_a_protected_network_has_no_password(): void
    {
        config(['dashboard.wifi.password' => null]);

        Livewire::test(WifiQr::class)->assertSee('WLAN noch nicht hinterlegt');
    }

    public function test_an_open_network_still_gets_a_code(): void
    {
        config([
            'dashboard.wifi.encryption' => 'none',
            'dashboard.wifi.password' => null,
        ]);

        $html = Livewire::test(WifiQr::class)->html();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('Offen', $html);
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        Livewire::test(WifiQr::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }
}
