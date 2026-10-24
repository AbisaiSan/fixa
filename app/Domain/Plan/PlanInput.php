<?php

namespace App\Domain\Plan;

use Carbon\CarbonImmutable;

/**
 * O que o gerador precisa saber do aluno, sem nada do banco (PLA-01, PLA-02).
 * As datas são dias do calendário no fuso do aluno (GAM-06).
 */
final readonly class PlanInput
{
    /**
     * @param  list<LessonActivity>  $lessonActivities  atividades prontas da aula mais recente
     */
    public function __construct(
        public CarbonImmutable $weekStart,
        public CarbonImmutable $from,
        public int $goalMinutes,
        public ?int $lessonId,
        public array $lessonActivities,
        public bool $hasCards,
        public bool $hasMistakes,
    ) {}
}
