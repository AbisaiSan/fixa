<?php

namespace App\Domain\Plan;

use Carbon\CarbonImmutable;

/**
 * Quando o modo corrida conta para a sequência (J5-B5, PLA-04, GAM-01): ao
 * menos um cartão respondido desde o início e os 5 minutos cumpridos, ou a
 * fila de vencidos zerada antes disso.
 */
final class RushRule
{
    public const SECONDS = 5 * 60;

    /** Folga para a diferença entre o relógio da tela e o do servidor. */
    public const TOLERANCE_SECONDS = 15;

    public static function completes(?CarbonImmutable $startedAt, CarbonImmutable $now, int $reviews, int $dueLeft): bool
    {
        if ($startedAt === null || $reviews < 1) {
            return false;
        }

        return $dueLeft === 0 || $startedAt->diffInSeconds($now) >= self::SECONDS - self::TOLERANCE_SECONDS;
    }
}
