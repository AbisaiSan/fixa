<?php

namespace App\Domain\Progress;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Sequência e congelamentos de um aluno, sem nada do banco (GAM-01, GAM-02).
 * As datas são dias do calendário no fuso do aluno (GAM-06).
 */
final readonly class StreakState
{
    public function __construct(
        public int $current,
        public int $longest,
        public int $freezes,
        public string $settledThrough,
        public string $lastGrantWeek,
    ) {}

    /**
     * Quem começa ganha o congelamento da semana em curso.
     */
    public static function start(string $firstDay): self
    {
        $day = CarbonImmutable::parse($firstDay);

        return new self(
            current: 0,
            longest: 0,
            freezes: 1,
            settledThrough: $day->subDay()->toDateString(),
            lastGrantWeek: $day->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
        );
    }
}
