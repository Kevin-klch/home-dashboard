<?php

namespace App\Services\Weather;

/**
 * Übersetzt die WMO-Wettercodes von Open-Meteo in etwas Darstellbares.
 *
 * @see https://open-meteo.com/en/docs — Abschnitt "WMO Weather interpretation codes"
 */
enum WeatherCondition: string
{
    case Clear = 'clear';
    case MainlyClear = 'mainly_clear';
    case PartlyCloudy = 'partly_cloudy';
    case Overcast = 'overcast';
    case Fog = 'fog';
    case Drizzle = 'drizzle';
    case Rain = 'rain';
    case FreezingRain = 'freezing_rain';
    case Snow = 'snow';
    case Showers = 'showers';
    case Thunderstorm = 'thunderstorm';
    case Unknown = 'unknown';

    public static function fromWmoCode(?int $code): self
    {
        return match ($code) {
            0 => self::Clear,
            1 => self::MainlyClear,
            2 => self::PartlyCloudy,
            3 => self::Overcast,
            45, 48 => self::Fog,
            51, 53, 55, 56, 57 => self::Drizzle,
            61, 63, 65 => self::Rain,
            66, 67 => self::FreezingRain,
            71, 73, 75, 77, 85, 86 => self::Snow,
            80, 81, 82 => self::Showers,
            95, 96, 99 => self::Thunderstorm,
            default => self::Unknown,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Clear => 'Klar',
            self::MainlyClear => 'Überwiegend klar',
            self::PartlyCloudy => 'Leicht bewölkt',
            self::Overcast => 'Bedeckt',
            self::Fog => 'Nebel',
            self::Drizzle => 'Nieselregen',
            self::Rain => 'Regen',
            self::FreezingRain => 'Gefrierender Regen',
            self::Snow => 'Schnee',
            self::Showers => 'Schauer',
            self::Thunderstorm => 'Gewitter',
            self::Unknown => 'Unbekannt',
        };
    }

    /** Name eines Icons aus der x-dash.icon-Komponente. */
    public function icon(): string
    {
        return match ($this) {
            self::Clear, self::MainlyClear => 'sun',
            self::PartlyCloudy => 'cloud-sun',
            self::Overcast => 'cloud',
            self::Fog => 'fog',
            self::Drizzle, self::Rain, self::FreezingRain, self::Showers => 'rain',
            self::Snow => 'snow',
            self::Thunderstorm => 'bolt',
            self::Unknown => 'cloud',
        };
    }
}
