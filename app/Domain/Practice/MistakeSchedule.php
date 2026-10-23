<?php

namespace App\Domain\Practice;

use Carbon\CarbonImmutable;

/**
 * Quando o erro volta e quando sai do caderno (J4-B9, ERR-02, ERR-03).
 *
 * - Cada erro no ponto soma uma repetição, zera os acertos seguidos e faz o
 *   ponto voltar no dia seguinte. Na terceira repetição, ganha prioridade.
 * - Cada acerto no ponto empurra a volta para 3 e depois 7 dias. O terceiro
 *   acerto seguido tira o erro da lista. Um erro novo no mesmo ponto o traz
 *   de volta, do começo.
 * - A volta cai no início do dia, no fuso do aluno (GAM-06).
 */
final class MistakeSchedule
{
    /** Dias até a volta, depois do erro e de cada acerto. */
    public const STEPS = [1, 3, 7];

    public const PRIORITY_REPETITIONS = 3;

    public const CORRECT_TO_RESOLVE = 3;

    public static function afterError(MistakeState $state, CarbonImmutable $now, string $timezone): MistakeState
    {
        $repetitions = $state->repetitions + 1;

        return new MistakeState(
            repetitions: $repetitions,
            step: 0,
            correctStreak: 0,
            priority: $state->priority || $repetitions >= self::PRIORITY_REPETITIONS,
            dueAt: self::due($now, $timezone, self::STEPS[0]),
            resolved: false,
        );
    }

    public static function afterCorrect(MistakeState $state, CarbonImmutable $now, string $timezone): MistakeState
    {
        $streak = $state->correctStreak + 1;

        if ($streak >= self::CORRECT_TO_RESOLVE) {
            return new MistakeState(
                repetitions: $state->repetitions,
                step: $state->step,
                correctStreak: $streak,
                priority: $state->priority,
                dueAt: null,
                resolved: true,
            );
        }

        $step = min($state->step + 1, count(self::STEPS) - 1);

        return new MistakeState(
            repetitions: $state->repetitions,
            step: $step,
            correctStreak: $streak,
            priority: $state->priority,
            dueAt: self::due($now, $timezone, self::STEPS[$step]),
            resolved: false,
        );
    }

    private static function due(CarbonImmutable $now, string $timezone, int $days): CarbonImmutable
    {
        return $now->setTimezone($timezone)->startOfDay()->addDays($days)->utc();
    }
}
