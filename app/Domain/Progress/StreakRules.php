<?php

namespace App\Domain\Progress;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Sequência e congelamento (J5-B6, GAM-01, GAM-02), sem nada do banco.
 *
 * - Cada dia fechado em que o aluno cumpriu a meta (ou fez a corrida) soma 1.
 * - Toda segunda-feira chega 1 congelamento, acumulando até 2.
 * - Dia perdido com sequência ativa gasta um congelamento sozinho e a
 *   sequência continua. Sem congelamento, ela volta a zero. Sem sequência,
 *   nada é gasto.
 * - Só dias depois do último fechado contam, então fechar de novo não muda nada.
 */
final class StreakRules
{
    public const MAX_FREEZES = 2;

    /**
     * @param  array<string, bool>  $days  data => cumpriu a meta, em ordem
     */
    public static function settle(StreakState $state, array $days): Settlement
    {
        $current = $state->current;
        $longest = $state->longest;
        $freezes = $state->freezes;
        $settledThrough = $state->settledThrough;
        $lastGrantWeek = $state->lastGrantWeek;
        $frozen = [];

        foreach ($days as $date => $counted) {
            if ($date <= $settledThrough) {
                continue;
            }

            $week = CarbonImmutable::parse($date)->startOfWeek(CarbonInterface::MONDAY)->toDateString();
            if ($week > $lastGrantWeek) {
                $freezes = min(self::MAX_FREEZES, $freezes + 1);
                $lastGrantWeek = $week;
            }

            if ($counted) {
                $current++;
                $longest = max($longest, $current);
            } elseif ($current > 0 && $freezes > 0) {
                $freezes--;
                $frozen[] = $date;
            } else {
                $current = 0;
            }

            $settledThrough = $date;
        }

        return new Settlement(new StreakState($current, $longest, $freezes, $settledThrough, $lastGrantWeek), $frozen);
    }

    /**
     * Sequência na tela: hoje entra assim que a meta é cumprida.
     */
    public static function shown(StreakState $state, bool $todayCounted): int
    {
        return $state->current + ($todayCounted ? 1 : 0);
    }

    /**
     * Congelamentos na tela: o da semana nova aparece já na segunda.
     */
    public static function shownFreezes(StreakState $state, string $today): int
    {
        $week = CarbonImmutable::parse($today)->startOfWeek(CarbonInterface::MONDAY)->toDateString();

        return $week > $state->lastGrantWeek ? min(self::MAX_FREEZES, $state->freezes + 1) : $state->freezes;
    }
}
