<?php

namespace Tests\Unit;

use App\Services\Calendar\BirthdayName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BirthdayNameTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function titles(): array
    {
        return [
            'nur der Name' => ['Anna', 'Anna'],
            'Geburtstag davor' => ['Geburtstag Anna', 'Anna'],
            'mit Doppelpunkt' => ['Geburtstag: Anna', 'Anna'],
            'Geburtstag von' => ['Geburtstag von Anna Müller', 'Anna Müller'],
            'hat Geburtstag' => ['Anna hat Geburtstag', 'Anna'],
            'Genitiv mit Apostroph' => ["Anna's Geburtstag", 'Anna'],
            'typografischer Apostroph' => ['Anna’s Birthday', 'Anna'],
            'englisch' => ['Birthday of Anna', 'Anna'],
            'mit Gedankenstrich' => ['Anna – Geburtstag', 'Anna'],
            'Vor- und Nachname bleiben zusammen' => ['Geburtstag Anna Maria Müller', 'Anna Maria Müller'],
        ];
    }

    #[DataProvider('titles')]
    public function test_it_extracts_the_name(string $title, string $expected): void
    {
        $this->assertSame($expected, BirthdayName::from($title));
    }

    public function test_it_leaves_unknown_formats_untouched(): void
    {
        // Lieber ein Titel zu viel als ein abgeschnittener Name.
        $this->assertSame('Hochzeitstag Oma und Opa', BirthdayName::from('Hochzeitstag Oma und Opa'));
        $this->assertSame('Anna (30)', BirthdayName::from('  Anna (30)  '));
    }
}
