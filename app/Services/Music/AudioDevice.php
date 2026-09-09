<?php

namespace App\Services\Music;

final readonly class AudioDevice
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public bool $isActive,
        public ?int $volumePercent = null,
        /** Manche Geräte lassen sich anzeigen, aber nicht fernsteuern. */
        public bool $isRestricted = false,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromApi(array $raw): ?self
    {
        if (blank($raw['id'] ?? null)) {
            return null;
        }

        return new self(
            id: (string) $raw['id'],
            name: (string) ($raw['name'] ?? 'Unbekanntes Gerät'),
            type: (string) ($raw['type'] ?? 'Unknown'),
            isActive: (bool) ($raw['is_active'] ?? false),
            volumePercent: isset($raw['volume_percent']) ? (int) $raw['volume_percent'] : null,
            isRestricted: (bool) ($raw['is_restricted'] ?? false),
        );
    }

    /** Name eines Icons aus der x-dash.icon-Komponente. */
    public function icon(): string
    {
        return match (strtolower($this->type)) {
            'smartphone', 'tablet' => 'phone',
            'speaker', 'avr', 'stb', 'audiodongle', 'gameconsole' => 'volume',
            default => 'device',
        };
    }

    /**
     * @return array{id: string, name: string, type: string, icon: string, active: bool, restricted: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon(),
            'active' => $this->isActive,
            'restricted' => $this->isRestricted,
        ];
    }
}
