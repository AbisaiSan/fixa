<?php

namespace App\Domain\Plan;

final readonly class DayDraft
{
    /**
     * @param  string  $date  Y-m-d no fuso do aluno
     * @param  list<TaskDraft>  $tasks
     */
    public function __construct(
        public string $date,
        public array $tasks,
    ) {}

    public function minutes(): int
    {
        return array_sum(array_map(fn (TaskDraft $task) => $task->minutes, $this->tasks));
    }
}
