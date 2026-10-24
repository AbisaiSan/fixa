<?php

namespace App\Domain\Progress;

/**
 * Meta semanal de dias estudados (J5-B7, GAM-04): 5 dias, como no protótipo.
 */
final class WeeklyGoal
{
    public const DAYS = 5;

    public static function percent(int $studied): int
    {
        return min(100, intdiv(max(0, $studied) * 100, self::DAYS));
    }

    public static function reached(int $studied): bool
    {
        return $studied >= self::DAYS;
    }
}
