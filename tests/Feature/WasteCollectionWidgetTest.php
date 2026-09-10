<?php

namespace Tests\Feature;

use App\Livewire\Widgets\WasteCollection;
use App\Services\Calendar\WasteType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WasteCollectionWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-09 08:00', 'Europe/Berlin'));
        config(['dashboard.waste.ics_url' => 'https://abfallkalender.enni.de/ics-kalender/teststrasse']);
    }

    public function test_it_shows_the_next_collection(): void
    {
        $this->fakeWaste([
            '20260911' => 'Biotonne',
            '20260915' => 'Restabfall',
        ]);

        Livewire::test(WasteCollection::class)
            ->assertSeeHtml('data-next="2026-09-11"')
            ->assertSee('Biotonne')
            ->assertSee('übermorgen');
    }

    public function test_it_names_the_distance_in_words(): void
    {
        $this->fakeWaste(['20260909' => 'Restabfall']);

        Livewire::test(WasteCollection::class)->assertSee('heute');
    }

    public function test_tomorrow_is_called_tomorrow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-08 08:00', 'Europe/Berlin'));
        $this->fakeWaste(['20260909' => 'Restabfall']);

        Livewire::test(WasteCollection::class)->assertSee('morgen');
    }

    public function test_several_bins_on_one_day_share_a_row(): void
    {
        // Kommt in echten Kalendern vor – ohne Gruppierung stuende der Tag mehrfach da.
        Http::fake(['abfallkalender.enni.de/*' => Http::response(implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Test//EN',
            'BEGIN:VEVENT', 'UID:a@test', 'SUMMARY:Abholung Papier', 'DTSTART;VALUE=DATE:20260911', 'END:VEVENT',
            'BEGIN:VEVENT', 'UID:b@test', 'SUMMARY:Abholung Gelber Sack', 'DTSTART;VALUE=DATE:20260911', 'END:VEVENT',
            'END:VCALENDAR', '',
        ]))]);

        $html = Livewire::test(WasteCollection::class)->html();

        $this->assertSame(1, substr_count($html, 'data-next="2026-09-11"'));
        $this->assertStringContainsString('Papier', $html);
        $this->assertStringContainsString('Gelber Sack', $html);
    }

    public function test_it_lists_the_following_dates(): void
    {
        $this->fakeWaste([
            '20260911' => 'Biotonne',
            '20260915' => 'Restabfall',
            '20260921' => 'Papier',
        ]);

        Livewire::test(WasteCollection::class)
            ->assertSeeHtml('data-day="2026-09-15"')
            ->assertSeeHtml('data-day="2026-09-21"');
    }

    public function test_it_asks_for_setup_without_a_feed(): void
    {
        config(['dashboard.waste.ics_url' => null]);

        Livewire::test(WasteCollection::class)
            ->assertSee('Abfuhrkalender nicht hinterlegt')
            ->assertSee('WASTE_ICS_URL');

        Http::assertNothingSent();
    }

    public function test_it_falls_back_when_the_feed_is_unreachable(): void
    {
        Http::fake(['abfallkalender.enni.de/*' => Http::response('', 500)]);

        Livewire::test(WasteCollection::class)->assertSee('Kalender nicht erreichbar');
    }

    public function test_it_reports_an_empty_feed_separately(): void
    {
        Http::fake(['abfallkalender.enni.de/*' => Http::response(
            "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//EN\r\nEND:VCALENDAR\r\n"
        )]);

        Livewire::test(WasteCollection::class)
            ->assertSee('Keine Termine bekannt')
            ->assertDontSee('nicht erreichbar');
    }

    public function test_the_page_shows_the_full_date(): void
    {
        $this->fakeWaste(['20260911' => 'Biotonne']);

        Livewire::test(WasteCollection::class, ['variant' => 'page'])
            ->assertSee('Freitag')
            ->assertSee('11. September 2026');
    }

    public function test_it_uses_its_own_source_and_not_the_appointment_calendar(): void
    {
        config(['dashboard.calendar.ics_url' => 'https://calendar.google.com/calendar/ical/x/basic.ics']);
        $this->fakeWaste(['20260911' => 'Biotonne']);

        Livewire::test(WasteCollection::class);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'calendar.google.com'));
    }

    /**
     * Andere Entsorger formulieren anders – deshalb Stichwörter statt Gleichheit.
     *
     * @return array<string, array{string, WasteType}>
     */
    public static function titles(): array
    {
        return [
            'ENNI Restabfall' => ['Abholung Restabfall', WasteType::Residual],
            'ENNI Papier' => ['Abholung Papier', WasteType::Paper],
            'ENNI Gelber Sack' => ['Abholung Gelber Sack', WasteType::YellowBag],
            'ENNI Biotonne' => ['Abholung Biotonne', WasteType::Bio],
            'ENNI Gruenschnitt' => ['Abholung Grünschnitt', WasteType::GreenWaste],
            'Restmuell' => ['Restmüll', WasteType::Residual],
            'Gelbe Tonne' => ['Gelbe Tonne', WasteType::YellowBag],
            'Papier und Pappe' => ['Papier/Pappe', WasteType::Paper],
            'Bioabfall' => ['Bioabfall', WasteType::Bio],
            'Gartenabfall' => ['Gartenabfall', WasteType::GreenWaste],
            'unbekannt' => ['Sperrmuellanmeldung', WasteType::Unknown],
        ];
    }

    #[DataProvider('titles')]
    public function test_it_recognises_the_waste_type(string $title, WasteType $expected): void
    {
        $this->assertSame($expected, WasteType::fromTitle($title));
    }

    public function test_every_type_has_a_label_and_colours(): void
    {
        foreach (WasteType::cases() as $type) {
            $this->assertNotSame('', $type->label());

            foreach (['dot', 'chip', 'text'] as $key) {
                $this->assertArrayHasKey($key, $type->colors());
                $this->assertNotSame('', $type->colors()[$key]);
            }
        }
    }
}
