<?php

use App\Domain\Plan\LessonActivity;
use App\Domain\Plan\PlannedDay;
use App\Domain\Plan\PlannedTask;
use App\Domain\Plan\PlanRecalculator;
use App\Domain\Plan\TaskKind;

function planned(int $id, string $date, TaskKind $kind, ?LessonActivity $activity = null, int $minutes = 4, int $lesson = 7): PlannedTask
{
    return new PlannedTask($id, $date, $kind, $activity, $kind === TaskKind::Lesson ? $lesson : null, $minutes);
}

/**
 * Dia padrão do plano: 3 + 4 + 3 na meta de 10.
 */
function plannedDay(string $date, LessonActivity $activity, int $goal = 10, int $firstId = 100): PlannedDay
{
    return new PlannedDay($date, $goal, [
        planned($firstId, $date, TaskKind::Flashcards, minutes: 3),
        planned($firstId + 1, $date, TaskKind::Lesson, $activity),
        planned($firstId + 2, $date, TaskKind::WeakPoint, minutes: 3),
    ]);
}

test('a missed lesson activity moves to a remaining day within 1.5 times the goal (PLA-03)', function () {
    $result = PlanRecalculator::recalculate(
        [planned(1, '2026-10-28', TaskKind::Lesson, LessonActivity::Reading)],
        [plannedDay('2026-10-30', LessonActivity::Writing), plannedDay('2026-10-31', LessonActivity::Vocabulary, firstId: 200)],
    );

    expect($result->moves)->toBe([1 => '2026-10-30'])
        ->and($result->dropped)->toBe([]);
});

test('missing 1 day never takes any day above 1.5 times the goal', function () {
    $result = PlanRecalculator::recalculate(
        [planned(1, '2026-10-28', TaskKind::Lesson, LessonActivity::Reading, minutes: 6)],
        [plannedDay('2026-10-30', LessonActivity::Writing)],
    );

    // 10 + 6 = 16 passaria de 15: não cabe e é descartada, nunca acumulada.
    expect($result->moves)->toBe([])
        ->and($result->dropped)->toBe([1]);
});

test('missing 2 days spreads the tasks over different days, never above 1.5 times the goal', function () {
    $upcoming = [
        plannedDay('2026-10-30', LessonActivity::Writing),
        plannedDay('2026-10-31', LessonActivity::Vocabulary, firstId: 200),
        plannedDay('2026-11-01', LessonActivity::Writing, firstId: 300),
    ];

    $result = PlanRecalculator::recalculate([
        planned(1, '2026-10-27', TaskKind::Lesson, LessonActivity::Reading),
        planned(2, '2026-10-27', TaskKind::WeakPoint, minutes: 3),
        planned(3, '2026-10-28', TaskKind::Lesson, LessonActivity::Vocabulary),
        planned(4, '2026-10-28', TaskKind::WeakPoint, minutes: 3),
    ], $upcoming);

    // A leitura vai para o primeiro dia; o vocabulário não repete o do dia
    // 31 e vai para o seguinte com menos remanejadas. O ponto fraco já
    // existe em todo dia e é descartado.
    expect($result->moves)->toBe([1 => '2026-10-30', 3 => '2026-11-01'])
        ->and($result->dropped)->toBe([2, 4]);

    foreach ($upcoming as $day) {
        $moved = array_sum(array_map(
            fn (int $id) => 4,
            array_keys(array_filter($result->moves, fn (string $date) => $date === $day->date)),
        ));
        expect($day->minutes() + $moved)->toBeLessThanOrEqual(15);
    }
});

test('more missed tasks than room drops the rest instead of piling up (PLA-03)', function () {
    $missed = [];
    foreach (range(1, 6) as $id) {
        $missed[] = planned($id, '2026-10-27', TaskKind::Lesson, LessonActivity::Reading, minutes: 4, lesson: $id);
    }

    $result = PlanRecalculator::recalculate($missed, [
        plannedDay('2026-10-31', LessonActivity::Writing),
        plannedDay('2026-11-01', LessonActivity::Writing, firstId: 200),
    ]);

    // Cada dia de 10 minutos só recebe mais 5: uma tarefa de 4 em cada.
    expect($result->moves)->toBe([1 => '2026-10-31', 2 => '2026-11-01'])
        ->and($result->dropped)->toBe([3, 4, 5, 6]);
});

test('the cap follows the goal of each day', function () {
    $result = PlanRecalculator::recalculate(
        [planned(1, '2026-10-27', TaskKind::Lesson, LessonActivity::Reading, minutes: 12)],
        [new PlannedDay('2026-10-30', 30, [planned(9, '2026-10-30', TaskKind::Lesson, LessonActivity::Writing, minutes: 30)])],
    );

    // 30 + 12 = 42, abaixo de 45.
    expect($result->moves)->toBe([1 => '2026-10-30']);
});

test('no remaining days drops everything', function () {
    $result = PlanRecalculator::recalculate([planned(1, '2026-10-31', TaskKind::Lesson, LessonActivity::Reading)], []);

    expect($result->moves)->toBe([])
        ->and($result->dropped)->toBe([1]);
});

test('flashcards are never moved, the next queue already brings the due cards', function () {
    $result = PlanRecalculator::recalculate(
        [planned(1, '2026-10-28', TaskKind::Flashcards, minutes: 3)],
        [new PlannedDay('2026-10-30', 10, [])],
    );

    expect($result->moves)->toBe([])
        ->and($result->dropped)->toBe([1]);
});

test('nothing missed changes nothing', function () {
    $result = PlanRecalculator::recalculate([], [plannedDay('2026-10-30', LessonActivity::Writing)]);

    expect($result->moves)->toBe([])
        ->and($result->dropped)->toBe([]);
});
