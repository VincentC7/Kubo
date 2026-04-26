<?php

namespace App\Tests\Unit;

use App\Service\WeekHelper;
use PHPUnit\Framework\TestCase;

class WeekHelperTest extends TestCase
{
    // ── validate() ────────────────────────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\DataProvider('validWeeks')]
    public function testValidateAcceptsValidWeeks(string $week): void
    {
        $this->assertTrue(WeekHelper::validate($week));
    }

    public static function validWeeks(): array
    {
        return [
            ['2026-W01'],
            ['2026-W18'],
            ['2026-W53'],
            ['2099-W52'],
            ['2000-W10'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidWeeks')]
    public function testValidateRejectsInvalidWeeks(string $week): void
    {
        $this->assertFalse(WeekHelper::validate($week));
    }

    public static function invalidWeeks(): array
    {
        return [
            ['2026-W00'],   // semaine 0 invalide
            ['2026-W54'],   // semaine 54 invalide
            ['2026W18'],    // pas de tiret
            ['26-W18'],     // année sur 2 chiffres
            ['2026-18'],    // pas de W
            ['abcd-W18'],   // non numérique
            [''],           // vide
            ['2026-W1'],    // W sans zéro
            ['2023-W53'],   // 2023 n'a que 52 semaines ISO
        ];
    }

    // ── current() ────────────────────────────────────────────────────────────

    public function testCurrentReturnsValidWeek(): void
    {
        $week = WeekHelper::current();
        $this->assertTrue(WeekHelper::validate($week), "current() doit retourner une semaine valide, got: $week");
    }

    public function testCurrentMatchesPhpIsoWeek(): void
    {
        $expected = (new \DateTimeImmutable())->format('o-\WW');
        $this->assertSame($expected, WeekHelper::current());
    }

    // ── bounds() ─────────────────────────────────────────────────────────────

    public function testBoundsReturnsCorrectDates(): void
    {
        $bounds = WeekHelper::bounds('2026-W18');

        // 2026-W18 : lundi 27 avril → dimanche 3 mai
        $this->assertSame('2026-04-27', $bounds['weekStart']);
        $this->assertSame('2026-05-03', $bounds['weekEnd']);
    }

    public function testBoundsWeekStartIsMonday(): void
    {
        $bounds = WeekHelper::bounds('2026-W18');
        $monday = new \DateTimeImmutable($bounds['weekStart']);
        $this->assertSame('1', $monday->format('N'), 'weekStart doit être un lundi (N=1)');
    }

    public function testBoundsWeekEndIsSunday(): void
    {
        $bounds = WeekHelper::bounds('2026-W18');
        $sunday = new \DateTimeImmutable($bounds['weekEnd']);
        $this->assertSame('7', $sunday->format('N'), 'weekEnd doit être un dimanche (N=7)');
    }

    public function testBoundsSpanSevenDays(): void
    {
        $bounds = WeekHelper::bounds('2026-W18');
        $start  = new \DateTimeImmutable($bounds['weekStart']);
        $end    = new \DateTimeImmutable($bounds['weekEnd']);
        $diff   = (int) $start->diff($end)->days;
        $this->assertSame(6, $diff);
    }

    public function testBoundsFirstWeekOfYear(): void
    {
        $bounds = WeekHelper::bounds('2026-W01');
        $this->assertSame('2025-12-29', $bounds['weekStart']); // ISO W01 2026 commence le 29/12/2025
        $this->assertSame('2026-01-04', $bounds['weekEnd']);
    }
}
