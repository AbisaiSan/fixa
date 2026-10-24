<?php

namespace App\Domain\Plan;

use App\Models\Enrollment;
use Carbon\CarbonInterface;

/**
 * Gerador do plano da semana (J5-B1, PLA-01, PLA-02), sem nada do banco.
 *
 * - A semana vai de segunda a domingo. Quem começa no meio dela recebe só
 *   os dias que faltam.
 * - Cada dia tem flashcards vencidos, uma atividade da aula mais recente e
 *   um ponto fraco, que dividem a meta diária na proporção 3, 4 e 3.
 * - A atividade da aula gira entre escrita, leitura e vocabulário conforme o
 *   dia da semana, usando só as que a aula já tem prontas.
 * - Bloco sem conteúdo fica de fora e os outros dividem a meta inteira.
 */
final class WeeklyPlanGenerator
{
    /**
     * @return list<DayDraft>
     */
    public static function generate(PlanInput $input): array
    {
        $monday = $input->weekStart->startOfDay()->startOfWeek(CarbonInterface::MONDAY);
        $sunday = $monday->addDays(6);
        $goal = max(Enrollment::MIN_DAILY_GOAL, min(Enrollment::MAX_DAILY_GOAL, $input->goalMinutes));

        $kinds = array_values(array_filter([
            $input->hasCards ? TaskKind::Flashcards : null,
            $input->lessonId !== null && $input->lessonActivities !== [] ? TaskKind::Lesson : null,
            $input->lessonId !== null || $input->hasMistakes ? TaskKind::WeakPoint : null,
        ]));
        $minutes = self::split($goal, $kinds);

        $days = [];
        for ($date = $input->from->startOfDay()->max($monday); $date->lte($sunday); $date = $date->addDay()) {
            $dayIndex = (int) $monday->diffInDays($date);

            $days[] = new DayDraft($date->toDateString(), array_map(
                fn (TaskKind $kind, int $index) => new TaskDraft(
                    kind: $kind,
                    minutes: $minutes[$index],
                    important: $kind->important(),
                    activity: $kind === TaskKind::Lesson
                        ? $input->lessonActivities[$dayIndex % count($input->lessonActivities)]
                        : null,
                    lessonId: $kind === TaskKind::Lesson ? $input->lessonId : null,
                ),
                $kinds,
                array_keys($kinds),
            ));
        }

        return $days;
    }

    /**
     * Divide a meta pelos pesos dos blocos, pelo maior resto: a soma é
     * sempre a meta, e no empate o resto vai para o primeiro bloco.
     *
     * @param  list<TaskKind>  $kinds
     * @return list<int>
     */
    public static function split(int $goal, array $kinds): array
    {
        if ($kinds === []) {
            return [];
        }

        $weights = array_map(fn (TaskKind $kind) => $kind->weight(), $kinds);
        $total = array_sum($weights);
        $minutes = array_map(fn (int $weight) => intdiv($goal * $weight, $total), $weights);
        $remainders = array_map(fn (int $weight) => ($goal * $weight) % $total, $weights);

        $order = array_keys($remainders);
        usort($order, fn (int $a, int $b) => [$remainders[$b], $a] <=> [$remainders[$a], $b]);

        foreach (array_slice($order, 0, $goal - array_sum($minutes)) as $index) {
            $minutes[$index]++;
        }

        return array_values($minutes);
    }
}
