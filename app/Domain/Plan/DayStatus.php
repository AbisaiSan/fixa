<?php

namespace App\Domain\Plan;

/**
 * Estado de um dia do plano (J5-B4, PLA-03, GAM-02). As datas são dias do
 * calendário no fuso do aluno (GAM-06).
 */
enum DayStatus: string
{
    case Done = 'done';
    case Today = 'today';
    case Missed = 'missed';
    case Frozen = 'frozen';
    case Planned = 'planned';

    public static function of(string $date, string $today, bool $counted, bool $frozen): self
    {
        if ($counted && $date <= $today) {
            return self::Done;
        }

        return match (true) {
            $date === $today => self::Today,
            $date > $today => self::Planned,
            $frozen => self::Frozen,
            default => self::Missed,
        };
    }
}
