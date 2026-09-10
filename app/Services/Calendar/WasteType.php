<?php

namespace App\Services\Calendar;

use Illuminate\Support\Str;

/**
 * Abfallart aus dem Titel eines Abfuhrtermins.
 *
 * ENNI schreibt "Abholung Restabfall", andere Entsorger formulieren anders –
 * deshalb wird auf Stichwörter geprüft statt auf exakte Gleichheit.
 */
enum WasteType: string
{
    case Residual = 'restabfall';
    case Paper = 'papier';
    case YellowBag = 'gelber-sack';
    case Bio = 'biotonne';
    case GreenWaste = 'gruenschnitt';
    case Unknown = 'unbekannt';

    public static function fromTitle(string $title): self
    {
        $haystack = Str::lower(Str::ascii($title));

        return match (true) {
            Str::contains($haystack, ['gelber sack', 'gelbe tonne', 'wertstoff', 'verpackung']) => self::YellowBag,
            Str::contains($haystack, ['bio', 'organik']) => self::Bio,
            Str::contains($haystack, ['papier', 'pappe', 'karton']) => self::Paper,
            Str::contains($haystack, ['grunschnitt', 'grunabfall', 'gartenabfall', 'laub']) => self::GreenWaste,
            Str::contains($haystack, ['restabfall', 'restmull', 'hausmull']) => self::Residual,
            default => self::Unknown,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Residual => 'Restabfall',
            self::Paper => 'Papier',
            self::YellowBag => 'Gelber Sack',
            self::Bio => 'Biotonne',
            self::GreenWaste => 'Grünschnitt',
            self::Unknown => 'Abfuhr',
        };
    }

    /**
     * Farbe der Tonne, so gut es die Palette hergibt.
     *
     * Vollständige Klassennamen, damit der Tailwind-Scanner sie findet.
     *
     * @return array{dot: string, chip: string, text: string}
     */
    public function colors(): array
    {
        return match ($this) {
            self::Residual => [
                'dot' => 'bg-slate-400',
                'chip' => 'bg-slate-400/15 text-slate-300',
                'text' => 'text-slate-300',
            ],
            self::Paper => [
                'dot' => 'bg-sky-400',
                'chip' => 'bg-sky-400/15 text-sky-300',
                'text' => 'text-sky-300',
            ],
            self::YellowBag => [
                'dot' => 'bg-yellow-400',
                'chip' => 'bg-yellow-400/15 text-yellow-300',
                'text' => 'text-yellow-300',
            ],
            self::Bio => [
                'dot' => 'bg-orange-400',
                'chip' => 'bg-orange-400/15 text-orange-300',
                'text' => 'text-orange-300',
            ],
            self::GreenWaste => [
                'dot' => 'bg-emerald-400',
                'chip' => 'bg-emerald-400/15 text-emerald-300',
                'text' => 'text-emerald-300',
            ],
            self::Unknown => [
                'dot' => 'bg-slate-500',
                'chip' => 'bg-slate-500/15 text-slate-400',
                'text' => 'text-slate-400',
            ],
        };
    }
}
