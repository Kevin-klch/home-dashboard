<?php

namespace App\Services\Wifi;

enum WifiEncryption: string
{
    case Wpa = 'WPA';
    case Wep = 'WEP';
    case Open = 'nopass';

    /**
     * Nimmt entgegen, was Leute üblicherweise in die .env schreiben.
     *
     * WPA3 wird bewusst auf WPA abgebildet: der Schlüssel "SAE" wird von
     * vielen Kameras noch nicht erkannt, "WPA" funktioniert dagegen auch bei
     * WPA3-Netzen zuverlässig.
     */
    public static function fromConfig(?string $value): self
    {
        return match (strtolower(trim((string) $value))) {
            'wep' => self::Wep,
            'none', 'open', 'nopass', '' => self::Open,
            default => self::Wpa,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Wpa => 'WPA/WPA2',
            self::Wep => 'WEP',
            self::Open => 'Offen',
        };
    }

    public function needsPassword(): bool
    {
        return $this !== self::Open;
    }
}
