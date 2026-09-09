<?php

namespace App\Services\Wifi;

final readonly class WifiNetwork
{
    public function __construct(
        public ?string $ssid,
        public ?string $password,
        public WifiEncryption $encryption,
        public bool $hidden = false,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('dashboard.wifi');

        return new self(
            ssid: $config['ssid'],
            password: $config['password'],
            encryption: WifiEncryption::fromConfig($config['encryption']),
            hidden: (bool) $config['hidden'],
        );
    }

    public function isConfigured(): bool
    {
        if (blank($this->ssid)) {
            return false;
        }

        // Ein geschütztes Netz ohne Passwort ergäbe einen Code, der nicht
        // verbindet – das ist schlimmer als gar keiner.
        return ! $this->encryption->needsPassword() || filled($this->password);
    }

    /**
     * Der Text, den die Kamera liest.
     *
     * Format nach der ZXing-Konvention, die Android und iOS beide verstehen:
     * WIFI:T:WPA;S:MeinNetz;P:geheim;;
     */
    public function toUri(): string
    {
        $parts = ['WIFI:T:'.$this->encryption->value];
        $parts[] = 'S:'.$this->escape((string) $this->ssid);

        if ($this->encryption->needsPassword()) {
            $parts[] = 'P:'.$this->escape((string) $this->password);
        }

        if ($this->hidden) {
            $parts[] = 'H:true';
        }

        // Doppeltes Semikolon am Ende gehört zum Format.
        return implode(';', $parts).';;';
    }

    /**
     * Sonderzeichen maskieren.
     *
     * Ohne das zerlegt ein Semikolon oder Doppelpunkt im Passwort den Code in
     * falsche Felder – der häufigste Grund, warum solche Codes nicht
     * funktionieren.
     */
    private function escape(string $value): string
    {
        return addcslashes($value, '\\;,:"');
    }
}
