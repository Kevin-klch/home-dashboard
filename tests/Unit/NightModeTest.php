<?php

namespace Tests\Unit;

use App\Services\Display\NightMode;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NightModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dashboard.night.enabled' => true,
            'dashboard.night.from' => '22:00',
            'dashboard.night.to' => '06:30',
            'dashboard.night.dim' => 0.78,
            'dashboard.night.wake_seconds' => 60,
        ]);
    }

    private function at(string $time): bool
    {
        return (new NightMode)->isNight(CarbonImmutable::parse("2026-09-10 {$time}"));
    }

    /**
     * Der Zeitraum läuft über Mitternacht – der Fall, den man beim Vergleichen
     * am leichtesten falsch macht.
     *
     * @return array<string, array{string, bool}>
     */
    public static function times(): array
    {
        return [
            'kurz vor Beginn' => ['21:59', false],
            'Beginn' => ['22:00', true],
            'kurz nach Beginn' => ['22:01', true],
            'vor Mitternacht' => ['23:59', true],
            'Mitternacht' => ['00:00', true],
            'tiefe Nacht' => ['03:17', true],
            'kurz vor Ende' => ['06:29', true],
            'Ende' => ['06:30', false],
            'Morgen' => ['08:00', false],
            'Mittag' => ['12:00', false],
            'Nachmittag' => ['17:45', false],
        ];
    }

    #[DataProvider('times')]
    public function test_it_knows_when_it_is_night(string $time, bool $expected): void
    {
        $this->assertSame($expected, $this->at($time));
    }

    public function test_a_window_inside_one_day_also_works(): void
    {
        config(['dashboard.night.from' => '13:00', 'dashboard.night.to' => '15:00']);

        $this->assertFalse($this->at('12:59'));
        $this->assertTrue($this->at('13:00'));
        $this->assertTrue($this->at('14:30'));
        $this->assertFalse($this->at('15:00'));
    }

    public function test_an_empty_window_never_dims(): void
    {
        config(['dashboard.night.from' => '22:00', 'dashboard.night.to' => '22:00']);

        $this->assertFalse($this->at('22:00'));
        $this->assertFalse($this->at('03:00'));
    }

    public function test_it_stays_bright_when_switched_off(): void
    {
        config(['dashboard.night.enabled' => false]);

        $this->assertFalse($this->at('03:00'));
    }

    public function test_nonsense_times_fall_back_instead_of_breaking(): void
    {
        // Ein Tippfehler in der .env darf die Anzeige nicht zerlegen.
        config(['dashboard.night.from' => 'abends', 'dashboard.night.to' => '25:99']);

        $mode = new NightMode;

        $this->assertSame('22:00', $mode->from());
        $this->assertSame('06:30', $mode->to());
    }

    public function test_the_dimming_stays_within_sane_bounds(): void
    {
        config(['dashboard.night.dim' => 5.0]);
        $this->assertSame(0.92, (new NightMode)->dim());

        config(['dashboard.night.dim' => -1.0]);
        $this->assertSame(0.0, (new NightMode)->dim());
    }

    public function test_the_wake_time_has_a_lower_bound(): void
    {
        config(['dashboard.night.wake_seconds' => 1]);

        $this->assertSame(5, (new NightMode)->wakeSeconds());
    }

    public function test_it_hands_the_browser_everything_it_needs(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-10 23:30'));

        $data = (new NightMode)->toArray();

        $this->assertSame(
            ['enabled', 'from', 'to', 'dim', 'wakeSeconds', 'night'],
            array_keys($data)
        );

        // Vorberechnet, damit nachts beim Laden nichts hell aufblitzt.
        $this->assertTrue($data['night']);
    }
}
