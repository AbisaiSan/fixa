<?php

namespace App\Domain\Plan;

/**
 * Um bloco já gravado no plano, sem nada do banco além do id.
 */
final readonly class PlannedTask
{
    public function __construct(
        public int $id,
        public string $date,
        public TaskKind $kind,
        public ?LessonActivity $activity,
        public ?int $lessonId,
        public int $minutes,
    ) {}

    /**
     * Dois blocos com a mesma chave no mesmo dia seriam a mesma atividade repetida.
     */
    public function key(): string
    {
        return implode(':', [$this->kind->value, $this->activity->value ?? '', $this->lessonId ?? '']);
    }
}
