<?php

namespace App\Domain\Plan;

final readonly class PlannedDay
{
    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function __construct(
        public string $date,
        public int $goalMinutes,
        public array $tasks,
    ) {}

    public function minutes(): int
    {
        return array_sum(array_map(fn (PlannedTask $task) => $task->minutes, $this->tasks));
    }

    /**
     * Teto do dia com remanejadas: 1,5 vez a meta (PLA-03).
     */
    public function cap(): int
    {
        return intdiv($this->goalMinutes * 3, 2);
    }
}
