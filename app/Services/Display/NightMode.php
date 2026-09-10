<?php

namespace App\Services\Display;

use Carbon\CarbonImmutable;

/**
 * Nachtabsenkung fürs Wandtablet.
 *
 * Der Zeitraum läuft in aller Regel über Mitternacht (22:00 bis 06:30) –
 * genau das ist der Fall, den man beim Vergleichen leicht falsch macht.
 */
final class NightMode
{
    public function enabled(): bool
    {
        return (bool) config('dashboard.night.enabled');
    }

    public function from(): string
    {
        return $this->normalise(config('dashboard.night.from'), '22:00');
    }

    public function to(): string
    {
        return $this->normalise(config('dashboard.night.to'), '06:30');
    }

    /** Wie stark abgedunkelt wird: 0 bleibt hell, 0.9 ist fast schwarz. */
    public function dim(): float
    {
        return max(0.0, min(0.92, (float) config('dashboard.night.dim')));
    }

    /** Wie lange eine Berührung wieder aufhellt. */
    public function wakeSeconds(): int
    {
        return max(5, (int) config('dashboard.night.wake_seconds'));
    }

    public function isNight(?CarbonImmutable $at = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $at ??= CarbonImmutable::now(config('app.timezone'));

        $now = $this->minutes($at->format('H:i'));
        $from = $this->minutes($this->from());
        $to = $this->minutes($this->to());

        if ($from === $to) {
            return false;
        }

        // Über Mitternacht: der Zeitraum ist die Vereinigung beider Enden.
        if ($from > $to) {
            return $now >= $from || $now < $to;
        }

        return $now >= $from && $now < $to;
    }

    /**
     * Für die Übergabe an den Browser.
     *
     * @return array{enabled: bool, from: string, to: string, dim: float, wakeSeconds: int, night: bool}
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled(),
            'from' => $this->from(),
            'to' => $this->to(),
            'dim' => $this->dim(),
            'wakeSeconds' => $this->wakeSeconds(),
            // Serverseitig vorberechnet, damit nachts nichts hell aufblitzt.
            'night' => $this->isNight(),
        ];
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }

    /** Fehleingaben in der .env dürfen die Anzeige nicht zerlegen. */
    private function normalise(mixed $value, string $fallback): string
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value) === 1
            ? (string) $value
            : $fallback;
    }
}
