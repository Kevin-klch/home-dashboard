<?php

namespace Tests\Feature;

use App\Livewire\Widgets\Birthdays;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BirthdaysWidgetTest extends TestCase
{
    private const URL = 'https://calendar.google.com/calendar/ical/geburtstage/basic.ics';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-09 08:00', 'Europe/Berlin'));
        config(['dashboard.birthdays.ics_url' => self::URL]);
    }

    /**
     * Ganztägige, jährlich wiederkehrende Einträge – so, wie sie in einem
     * selbst angelegten Geburtstagskalender stehen.
     *
     * @param  array<string, string>  $people  Name => erster Geburtstag (Ymd)
     */
    private function fakeCalendar(array $people): void
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Test//EN'];

        foreach ($people as $title => $date) {
            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'UID:'.md5($title).'@test',
                'DTSTART;VALUE=DATE:'.$date,
                'DTEND;VALUE=DATE:'.CarbonImmutable::parse($date)->addDay()->format('Ymd'),
                'RRULE:FREQ=YEARLY',
                'SUMMARY:'.$title,
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';
        $lines[] = '';

        Http::fake(['calendar.google.com/*' => Http::response(implode("\r\n", $lines))]);
    }

    public function test_it_celebrates_a_birthday_today(): void
    {
        $this->fakeCalendar(['Geburtstag Anna' => '19900909']);

        Livewire::test(Birthdays::class)
            ->assertSeeHtml('data-state="heute"')
            ->assertSee('Anna')
            ->assertSee('hat heute Geburtstag')
            ->assertDontSee('Nächster Geburtstag');
    }

    public function test_it_uses_the_plural_when_several_people_celebrate(): void
    {
        $this->fakeCalendar([
            'Geburtstag Anna' => '19900909',
            'Geburtstag Ben' => '19850909',
        ]);

        Livewire::test(Birthdays::class)
            ->assertSee('Anna und Ben')
            ->assertSee('haben heute Geburtstag');
    }

    public function test_it_shows_the_next_birthday_when_nobody_celebrates_today(): void
    {
        $this->fakeCalendar(['Geburtstag Anna' => '19900921']);

        Livewire::test(Birthdays::class)
            ->assertSeeHtml('data-state="naechster"')
            ->assertSee('Nächster Geburtstag')
            ->assertSee('Anna')
            ->assertSee('in 12 Tagen')
            ->assertSee('21. September');
    }

    public function test_it_says_tomorrow_instead_of_in_one_day(): void
    {
        $this->fakeCalendar(['Geburtstag Ben' => '19850910']);

        Livewire::test(Birthdays::class)
            ->assertSee('morgen')
            ->assertDontSee('in 1 Tagen');
    }

    public function test_it_shows_the_year_when_the_next_birthday_falls_into_the_next_one(): void
    {
        // Der 27. Juni ist dieses Jahr vorbei, der nächste liegt 2027.
        $this->fakeCalendar(['Geburtstag Kevin' => '19900627']);

        Livewire::test(Birthdays::class)->assertSee('27. Juni 2027');
    }

    public function test_it_lists_the_following_birthdays_below(): void
    {
        $this->fakeCalendar([
            'Geburtstag Anna' => '19900921',
            'Geburtstag Ben' => '19851001',
            'Geburtstag Carla' => '19801015',
        ]);

        Livewire::test(Birthdays::class)
            ->assertSeeHtml('data-later="Ben"')
            ->assertSeeHtml('data-later="Carla"')
            // Der Nächste steht schon oben und darf unten nicht wiederholt werden.
            ->assertDontSeeHtml('data-later="Anna"');
    }

    public function test_todays_birthday_does_not_push_the_next_one_out_of_the_list(): void
    {
        $this->fakeCalendar([
            'Geburtstag Anna' => '19900909',
            'Geburtstag Ben' => '19851001',
        ]);

        Livewire::test(Birthdays::class)
            ->assertSeeHtml('data-state="heute"')
            ->assertSeeHtml('data-later="Ben"');
    }

    public function test_it_asks_for_setup_when_no_url_is_configured(): void
    {
        config(['dashboard.birthdays.ics_url' => null]);

        Livewire::test(Birthdays::class)
            ->assertSee('Noch nicht verbunden')
            ->assertSee('BIRTHDAYS_ICS_URL');

        Http::assertNothingSent();
    }

    public function test_it_falls_back_when_the_feed_is_unreachable(): void
    {
        Http::fake(['calendar.google.com/*' => Http::response('', 500)]);

        Livewire::test(Birthdays::class)->assertSee('Kalender nicht erreichbar');
    }

    public function test_it_reports_an_empty_calendar_separately(): void
    {
        $this->fakeCalendar([]);

        Livewire::test(Birthdays::class)
            ->assertSee('Keine Geburtstage hinterlegt')
            ->assertDontSee('nicht erreichbar');
    }

    public function test_it_uses_its_own_source_and_not_the_appointment_calendar(): void
    {
        config([
            'dashboard.calendar.ics_url' => 'https://calendar.google.com/calendar/ical/termine/basic.ics',
            'dashboard.birthdays.ics_url' => self::URL,
        ]);

        $this->fakeCalendar(['Geburtstag Anna' => '19900921']);

        Livewire::test(Birthdays::class);

        Http::assertSent(fn ($request) => $request->url() === self::URL);
    }

    public function test_it_applies_the_grid_classes_from_the_dashboard(): void
    {
        $this->fakeCalendar(['Geburtstag Anna' => '19900921']);

        Livewire::test(Birthdays::class, ['class' => 'col-span-2'])
            ->assertSeeHtml('col-span-2');
    }
}
