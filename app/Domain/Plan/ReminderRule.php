<?php

namespace App\Domain\Plan;

use Carbon\CarbonImmutable;

/**
 * Lembrete diário por e-mail (J5-B10), opcional. Vai uma vez por dia, a
 * partir da hora escolhida no fuso do aluno (GAM-06), e só para quem ainda
 * não cumpriu a meta de hoje.
 */
final class ReminderRule
{
    public const HOURS = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];

    public const DEFAULT_HOUR = 19;

    public static function due(bool $enabled, int $hour, CarbonImmutable $localNow, ?string $sentOn, bool $todayCounted): bool
    {
        return $enabled
            && ! $todayCounted
            && $localNow->hour >= $hour
            && $sentOn !== $localNow->toDateString();
    }
}
