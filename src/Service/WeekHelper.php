<?php

namespace App\Service;

final class WeekHelper
{
    public static function validate(string $week): bool
    {
        if (!preg_match('/^(\d{4})-W(0[1-9]|[1-4]\d|5[0-3])$/', $week, $m)) {
            return false;
        }

        $year    = (int) $m[1];
        $weekNum = (int) $m[2];

        // Vérifie que la semaine existe réellement dans cette année ISO
        // En créant une date avec setISODate et en vérifiant que l'année ISO reste la même
        $date = (new \DateTimeImmutable())->setISODate($year, $weekNum, 1);

        return (int) $date->format('o') === $year;
    }

    public static function current(): string
    {
        return (new \DateTimeImmutable())->format('o-\WW');
    }

    /**
     * @return array{weekStart: string, weekEnd: string}
     */
    public static function bounds(string $week): array
    {
        // e.g. "2026-W18" → year=2026, week=18
        [$year, $w] = explode('-W', $week);
        $monday = new \DateTimeImmutable();
        $monday = $monday->setISODate((int) $year, (int) $w, 1);
        $sunday = $monday->modify('+6 days');

        return [
            'weekStart' => $monday->format('Y-m-d'),
            'weekEnd'   => $sunday->format('Y-m-d'),
        ];
    }
}
