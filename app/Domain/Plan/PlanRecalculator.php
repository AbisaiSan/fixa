<?php

namespace App\Domain\Plan;

/**
 * Recálculo depois de um dia sem estudo (J5-B2, PLA-03), sem nada do banco.
 *
 * - Só tarefas importantes atrasadas são remanejadas. Os flashcards voltam
 *   sozinhos na fila do dia seguinte.
 * - Cada tarefa vai para o dia restante com menos remanejadas (e, no
 *   empate, o mais cedo), sem repetir no dia um bloco igual e sem passar de
 *   1,5 vez a meta daquele dia.
 * - O que não couber é descartado, nunca acumulado como dívida.
 */
final class PlanRecalculator
{
    /**
     * @param  list<PlannedTask>  $overdue  tarefas não feitas dos dias que passaram, na ordem do plano
     * @param  list<PlannedDay>  $upcoming  dias que ainda vêm, em ordem
     */
    public static function recalculate(array $overdue, array $upcoming): Recalculation
    {
        $minutes = [];
        $keys = [];
        $movedCount = [];
        foreach ($upcoming as $index => $day) {
            $minutes[$index] = $day->minutes();
            $keys[$index] = array_map(fn (PlannedTask $task) => $task->key(), $day->tasks);
            $movedCount[$index] = 0;
        }

        $moves = [];
        $dropped = [];

        foreach ($overdue as $task) {
            $target = $task->kind->important() ? self::target($task, $upcoming, $minutes, $keys, $movedCount) : null;

            if ($target === null) {
                $dropped[] = $task->id;

                continue;
            }

            $moves[$task->id] = $upcoming[$target]->date;
            $minutes[$target] += $task->minutes;
            $keys[$target][] = $task->key();
            $movedCount[$target]++;
        }

        return new Recalculation($moves, $dropped);
    }

    /**
     * @param  list<PlannedDay>  $upcoming
     * @param  array<int, int>  $minutes
     * @param  array<int, list<string>>  $keys
     * @param  array<int, int>  $movedCount
     */
    private static function target(PlannedTask $task, array $upcoming, array $minutes, array $keys, array $movedCount): ?int
    {
        $best = null;

        foreach ($upcoming as $index => $day) {
            $fits = $day->cap() >= $minutes[$index] + $task->minutes
                && ! in_array($task->key(), $keys[$index], true);

            if ($fits && ($best === null || $movedCount[$index] < $movedCount[$best])) {
                $best = $index;
            }
        }

        return $best;
    }
}
