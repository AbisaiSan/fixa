<?php

namespace App\Domain\Plan;

final readonly class Recalculation
{
    /**
     * @param  array<int, string>  $moves  id da tarefa => data de destino
     * @param  list<int>  $dropped  ids descartados
     */
    public function __construct(
        public array $moves,
        public array $dropped,
    ) {}
}
