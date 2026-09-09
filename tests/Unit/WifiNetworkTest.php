<?php

namespace Tests\Unit;

use App\Services\Wifi\WifiEncryption;
use App\Services\Wifi\WifiNetwork;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WifiNetworkTest extends TestCase
{
    private function network(
        ?string $ssid = 'MeinNetz',
        ?string $password = 'geheim',
        WifiEncryption $encryption = WifiEncryption::Wpa,
        bool $hidden = false,
    ): WifiNetwork {
        return new WifiNetwork($ssid, $password, $encryption, $hidden);
    }

    public function test_it_builds_the_standard_format(): void
    {
        $this->assertSame(
            'WIFI:T:WPA;S:MeinNetz;P:geheim;;',
            $this->network()->toUri()
        );
    }

    public function test_an_open_network_carries_no_password(): void
    {
        $uri = $this->network(password: null, encryption: WifiEncryption::Open)->toUri();

        $this->assertSame('WIFI:T:nopass;S:MeinNetz;;', $uri);
        $this->assertStringNotContainsString('P:', $uri);
    }

    public function test_a_hidden_network_is_marked(): void
    {
        $this->assertStringContainsString(
            'H:true',
            $this->network(hidden: true)->toUri()
        );
    }

    /**
     * Ohne Maskierung zerlegen diese Zeichen den Code in falsche Felder –
     * der häufigste Grund für einen QR-Code, der nicht verbindet.
     *
     * @return array<string, array{string, string}>
     */
    public static function specialCharacters(): array
    {
        return [
            'Semikolon' => ['ab;cd', 'P:ab\;cd'],
            'Doppelpunkt' => ['ab:cd', 'P:ab\:cd'],
            'Komma' => ['ab,cd', 'P:ab\,cd'],
            'Backslash' => ['ab\\cd', 'P:ab\\\\cd'],
            'Anführungszeichen' => ['ab"cd', 'P:ab\"cd'],
            'mehrere gemischt' => ['a;b:c,d', 'P:a\;b\:c\,d'],
        ];
    }

    #[DataProvider('specialCharacters')]
    public function test_it_escapes_special_characters_in_the_password(string $password, string $expected): void
    {
        $this->assertStringContainsString($expected, $this->network(password: $password)->toUri());
    }

    public function test_it_escapes_special_characters_in_the_network_name(): void
    {
        $this->assertStringContainsString(
            'S:Haus\;Hof',
            $this->network(ssid: 'Haus;Hof')->toUri()
        );
    }

    public function test_ordinary_characters_stay_untouched(): void
    {
        // Umlaute, Leerzeichen und Sonderzeichen ohne Bedeutung im Format
        // dürfen nicht angefasst werden.
        $uri = $this->network(ssid: 'Kevins WLAN', password: 'Grüße!#42-ß')->toUri();

        $this->assertStringContainsString('S:Kevins WLAN', $uri);
        $this->assertStringContainsString('P:Grüße!#42-ß', $uri);
    }

    public function test_it_is_not_configured_without_a_network_name(): void
    {
        $this->assertFalse($this->network(ssid: null)->isConfigured());
        $this->assertFalse($this->network(ssid: '  ')->isConfigured());
    }

    public function test_a_protected_network_without_a_password_counts_as_unconfigured(): void
    {
        // Ein solcher Code ließe sich scannen, würde aber nie verbinden.
        $this->assertFalse($this->network(password: null)->isConfigured());
        $this->assertFalse($this->network(password: '')->isConfigured());
    }

    public function test_an_open_network_needs_no_password_to_be_configured(): void
    {
        $this->assertTrue(
            $this->network(password: null, encryption: WifiEncryption::Open)->isConfigured()
        );
    }

    /**
     * @return array<string, array{?string, WifiEncryption}>
     */
    public static function encryptionValues(): array
    {
        return [
            'WPA' => ['WPA', WifiEncryption::Wpa],
            'wpa2 klein' => ['wpa2', WifiEncryption::Wpa],
            'WPA3' => ['WPA3', WifiEncryption::Wpa],
            'WEP' => ['wep', WifiEncryption::Wep],
            'none' => ['none', WifiEncryption::Open],
            'offen' => ['open', WifiEncryption::Open],
            'leer' => ['', WifiEncryption::Open],
            'null' => [null, WifiEncryption::Open],
            'unbekannt fällt auf WPA zurück' => ['irgendwas', WifiEncryption::Wpa],
        ];
    }

    #[DataProvider('encryptionValues')]
    public function test_it_reads_the_encryption_from_config(?string $value, WifiEncryption $expected): void
    {
        $this->assertSame($expected, WifiEncryption::fromConfig($value));
    }
}
