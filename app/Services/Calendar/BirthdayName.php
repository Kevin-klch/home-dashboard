<?php

namespace App\Services\Calendar;

/**
 * Holt den Namen aus dem Titel eines Geburtstagstermins.
 *
 * Je nachdem, ob ein Eintrag von Hand angelegt oder aus einer anderen Quelle
 * übernommen wurde, heißt er "Anna", "Geburtstag Anna" oder "Anna hat
 * Geburtstag". Auf dem Dashboard soll in allen Fällen nur "Anna" stehen.
 *
 * Bewusst konservativ: Was auf kein Muster passt, bleibt unverändert. Lieber
 * ein Titel zu viel als ein abgeschnittener Name.
 */
final class BirthdayName
{
    /**
     * Muster in der Reihenfolge, in der sie geprüft werden.
     *
     * @var list<string>
     */
    private const PATTERNS = [
        '/^geburtstag\s+von\s+(?<name>.+)$/iu',
        '/^geburtstag[:\s-]+(?<name>.+)$/iu',
        '/^(?<name>.+?)\s+hat\s+geburtstag$/iu',
        '/^(?<name>.+?)[\x{2019}\']s\s+(geburtstag|birthday)$/iu',
        '/^birthday\s+of\s+(?<name>.+)$/iu',
        '/^(?<name>.+?)\s*[–-]\s*geburtstag$/iu',
    ];

    public static function from(string $title): string
    {
        $title = trim($title);

        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $title, $matches) === 1) {
                $name = trim($matches['name']);

                if ($name !== '') {
                    return $name;
                }
            }
        }

        return $title;
    }
}
