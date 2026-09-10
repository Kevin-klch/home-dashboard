<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Kein Test darf echte HTTP-Aufrufe absetzen. Wer eine externe Quelle
        // braucht, faked sie explizit – sonst schlaegt der Test hier fehl.
        Http::preventStrayRequests();
    }

    /**
     * Antwort von Open-Meteo faken.
     *
     * Ohne Argument reicht es aus, damit das Wetter-Widget rendert; Tests, die
     * konkrete Werte pruefen, geben ihre eigene Nutzlast mit.
     *
     * @param  array<string, mixed>|null  $payload
     */
    protected function fakeOpenMeteo(?array $payload = null): void
    {
        $payload ??= [
            'current' => [
                'time' => now()->format('Y-m-d\TH:i'),
                'temperature_2m' => 18.0,
                'apparent_temperature' => 16.0,
                'weather_code' => 3,
            ],
            'daily' => [
                'time' => collect(range(0, 6))
                    ->map(fn (int $offset) => now()->addDays($offset)->toDateString())
                    ->all(),
                'weather_code' => array_fill(0, 7, 3),
                'temperature_2m_max' => array_fill(0, 7, 21.0),
                'temperature_2m_min' => array_fill(0, 7, 11.0),
            ],
        ];

        Http::fake(['api.open-meteo.com/*' => Http::response($payload)]);
    }

    /**
     * Abfuhrkalender faken.
     *
     * Ganztägige Termine ohne DTEND – genau die Form, die der ENNI-Feed
     * liefert.
     *
     * @param  array<string, string>  $collections  Datum (Ymd) => Abfallart
     */
    protected function fakeWaste(?array $collections = null): void
    {
        $collections ??= [
            now()->addDays(2)->format('Ymd') => 'Restabfall',
            now()->addDays(5)->format('Ymd') => 'Gelber Sack',
        ];

        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Test//EN'];

        foreach ($collections as $date => $type) {
            $lines = array_merge($lines, [
                'BEGIN:VEVENT',
                'UID:'.md5($date.$type).'@test',
                'SUMMARY:Abholung '.$type,
                'DTSTART;VALUE=DATE:'.$date,
                'LOCATION:Teststraße',
                'END:VEVENT',
            ]);
        }

        $lines[] = 'END:VCALENDAR';
        $lines[] = '';

        Http::fake(['abfallkalender.enni.de/*' => Http::response(implode("
", $lines))]);
    }

    /** Alles faken, was ein vollständiges Dashboard abruft. */
    protected function fakeDashboardSources(): void
    {
        $this->fakeOpenMeteo();
        $this->fakeWaste();
    }
}
