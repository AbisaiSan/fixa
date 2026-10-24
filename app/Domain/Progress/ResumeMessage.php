<?php

namespace App\Domain\Progress;

use Carbon\CarbonImmutable;

/**
 * Mensagem de retomada (J5-B8, GAM-05). O servidor só escolhe o código; os
 * textos ficam todos no bloco "resume" de resources/js/i18n/pt-BR.ts.
 * Sem punição: nenhuma mensagem diz quantos dias o aluno ficou fora.
 */
enum ResumeMessage: string
{
    case WelcomeBack = 'welcome_back';
    case FreezeUsed = 'freeze_used';

    public static function for(?string $lastStudied, string $today, bool $yesterdayFrozen): ?self
    {
        if ($lastStudied === null) {
            return null;
        }

        $day = CarbonImmutable::parse($today);
        $yesterday = $day->subDay()->toDateString();

        if ($lastStudied >= $yesterday) {
            return null;
        }

        $dayBefore = $day->subDays(2)->toDateString();

        return $yesterdayFrozen && $lastStudied === $dayBefore ? self::FreezeUsed : self::WelcomeBack;
    }
}
