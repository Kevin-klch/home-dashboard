<?php

namespace Tests\Unit;

use App\Services\Weather\WeatherCondition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WeatherConditionTest extends TestCase
{
    /**
     * @return array<string, array{int, WeatherCondition}>
     */
    public static function wmoCodes(): array
    {
        return [
            'klar' => [0, WeatherCondition::Clear],
            'überwiegend klar' => [1, WeatherCondition::MainlyClear],
            'leicht bewölkt' => [2, WeatherCondition::PartlyCloudy],
            'bedeckt' => [3, WeatherCondition::Overcast],
            'Nebel' => [45, WeatherCondition::Fog],
            'Reifnebel' => [48, WeatherCondition::Fog],
            'Niesel' => [53, WeatherCondition::Drizzle],
            'gefrierender Niesel' => [57, WeatherCondition::Drizzle],
            'Regen' => [63, WeatherCondition::Rain],
            'gefrierender Regen' => [66, WeatherCondition::FreezingRain],
            'Schnee' => [73, WeatherCondition::Snow],
            'Schneeschauer' => [86, WeatherCondition::Snow],
            'Regenschauer' => [81, WeatherCondition::Showers],
            'Gewitter' => [95, WeatherCondition::Thunderstorm],
            'Gewitter mit Hagel' => [99, WeatherCondition::Thunderstorm],
        ];
    }

    #[DataProvider('wmoCodes')]
    public function test_it_maps_wmo_codes(int $code, WeatherCondition $expected): void
    {
        $this->assertSame($expected, WeatherCondition::fromWmoCode($code));
    }

    public function test_unknown_and_missing_codes_fall_back(): void
    {
        $this->assertSame(WeatherCondition::Unknown, WeatherCondition::fromWmoCode(null));
        $this->assertSame(WeatherCondition::Unknown, WeatherCondition::fromWmoCode(1234));
    }

    public function test_every_condition_has_a_label_and_an_icon(): void
    {
        $icons = file_get_contents(__DIR__.'/../../resources/views/components/dash/icon.blade.php');

        foreach (WeatherCondition::cases() as $condition) {
            $this->assertNotSame('', $condition->label(), "Label fehlt für {$condition->value}");
            $this->assertNotSame('', $condition->icon(), "Icon fehlt für {$condition->value}");

            // Das Icon muss in der Icon-Komponente auch wirklich gezeichnet werden.
            $this->assertStringContainsString(
                "@case('{$condition->icon()}')",
                $icons,
                "Die Icon-Komponente kennt '{$condition->icon()}' nicht"
            );
        }
    }
}
