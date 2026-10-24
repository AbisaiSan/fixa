<?php

namespace App\Domain\Plan;

/**
 * Quando o dia conta para a sequência (J5-B4, GAM-01): ao fazer os minutos
 * da meta, ou tudo o que estava planejado num dia mais leve, ou o modo
 * corrida (PLA-04).
 */
final class DayProgress
{
    public static function counted(int $goal, int $planned, int $done, bool $rush): bool
    {
        if ($rush) {
            return true;
        }

        $target = min($goal, $planned);

        return $target > 0 && $done >= $target;
    }

    /**
     * Anel da meta: minutos feitos sobre a meta, até 100%.
     */
    public static function percent(int $goal, int $done): int
    {
        return $goal <= 0 ? 0 : min(100, intdiv($done * 100, $goal));
    }
}
