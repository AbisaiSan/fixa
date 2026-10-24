<?php

use App\Domain\Plan\DayDraft;
use App\Domain\Plan\LessonActivity;
use App\Domain\Plan\PlanInput;
use App\Domain\Plan\TaskDraft;
use App\Domain\Plan\WeeklyPlanGenerator;
use Carbon\CarbonImmutable;

function planInput(array $overrides = []): PlanInput
{
    return new PlanInput(...array_merge([
        // 26/10/2026 é uma segunda-feira.
        'weekStart' => CarbonImmutable::parse('2026-10-26'),
        'from' => CarbonImmutable::parse('2026-10-26'),
        'goalMinutes' => 10,
        'lessonId' => 7,
        'lessonActivities' => [LessonActivity::Writing, LessonActivity::Reading, LessonActivity::Vocabulary],
        'hasCards' => true,
        'hasMistakes' => false,
    ], $overrides));
}

/**
 * @param  list<DayDraft>  $days
 * @return list<list<string>>
 */
function kinds(array $days): array
{
    return array_map(fn (DayDraft $day) => array_map(fn (TaskDraft $task) => $task->kind->value, $day->tasks), $days);
}

test('the week runs from monday to sunday with 3 blocks a day (PLA-01, PLA-02)', function () {
    $days = WeeklyPlanGenerator::generate(planInput());

    expect($days)->toHaveCount(7)
        ->and($days[0]->date)->toBe('2026-10-26')
        ->and($days[6]->date)->toBe('2026-11-01');

    foreach (kinds($days) as $tasks) {
        expect($tasks)->toBe(['flashcards', 'lesson', 'weak_point']);
    }
});

test('the blocks fill exactly the daily goal (PLA-01)', function (int $goal, array $split) {
    $days = WeeklyPlanGenerator::generate(planInput(['goalMinutes' => $goal]));

    foreach ($days as $day) {
        expect(array_map(fn (TaskDraft $task) => $task->minutes, $day->tasks))->toBe($split)
            ->and($day->minutes())->toBe($goal);
    }
})->with([
    'minimum' => [5, [2, 2, 1]],
    'initial' => [10, [3, 4, 3]],
    'fifteen' => [15, [5, 6, 4]],
    'maximum' => [30, [9, 12, 9]],
]);

test('a goal outside 5 to 30 minutes is clamped (PLA-01)', function () {
    expect(WeeklyPlanGenerator::generate(planInput(['goalMinutes' => 2]))[0]->minutes())->toBe(5)
        ->and(WeeklyPlanGenerator::generate(planInput(['goalMinutes' => 90]))[0]->minutes())->toBe(30);
});

test('the lesson block rotates through the activities of the latest lesson (PLA-02)', function () {
    $days = WeeklyPlanGenerator::generate(planInput());

    $activities = array_map(fn (DayDraft $day) => $day->tasks[1]->activity?->value, $days);

    expect($activities)->toBe(['writing', 'reading', 'vocabulary', 'writing', 'reading', 'vocabulary', 'writing'])
        ->and($days[0]->tasks[1]->lessonId)->toBe(7);
});

test('a lesson with only the writing session repeats it every day', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['lessonActivities' => [LessonActivity::Writing]]));

    expect(array_unique(array_map(fn (DayDraft $day) => $day->tasks[1]->activity?->value, $days)))->toBe(['writing']);
});

test('lesson and weak point are important, flashcards are not (PLA-03)', function () {
    $tasks = WeeklyPlanGenerator::generate(planInput())[0]->tasks;

    expect(array_map(fn (TaskDraft $task) => $task->important, $tasks))->toBe([false, true, true]);
});

test('starting in the middle of the week plans only from that day to sunday', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['from' => CarbonImmutable::parse('2026-10-29')]));

    expect(array_map(fn (DayDraft $day) => $day->date, $days))
        ->toBe(['2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01'])
        // O rodízio segue o dia da semana, não o primeiro dia planejado.
        ->and($days[0]->tasks[1]->activity)->toBe(LessonActivity::Writing);
});

test('without a lesson the day has only flashcards, filling the goal', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['lessonId' => null, 'lessonActivities' => []]));

    expect(kinds($days)[0])->toBe(['flashcards'])
        ->and($days[0]->minutes())->toBe(10);
});

test('open mistakes keep the weak point block even without a lesson', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['lessonId' => null, 'lessonActivities' => [], 'hasMistakes' => true]));

    expect(kinds($days)[0])->toBe(['flashcards', 'weak_point'])
        ->and(array_map(fn (TaskDraft $task) => $task->minutes, $days[0]->tasks))->toBe([5, 5]);
});

test('a lesson without activities ready yet plans no lesson block', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['lessonActivities' => []]));

    expect(kinds($days)[0])->toBe(['flashcards', 'weak_point']);
});

test('nothing to study gives days with no blocks', function () {
    $days = WeeklyPlanGenerator::generate(planInput(['lessonId' => null, 'lessonActivities' => [], 'hasCards' => false]));

    expect($days)->toHaveCount(7)
        ->and(kinds($days)[0])->toBe([])
        ->and($days[0]->minutes())->toBe(0);
});

test('the week start is normalized to the monday of the given date', function () {
    $days = WeeklyPlanGenerator::generate(planInput([
        'weekStart' => CarbonImmutable::parse('2026-10-28'),
        'from' => CarbonImmutable::parse('2026-10-28'),
    ]));

    expect($days[0]->date)->toBe('2026-10-28')
        ->and($days[0]->tasks[1]->activity)->toBe(LessonActivity::Vocabulary)
        ->and(end($days)->date)->toBe('2026-11-01');
});

test('the week starts on monday even when the locale starts it on sunday', function () {
    CarbonImmutable::setLocale('pt_BR');

    try {
        $days = WeeklyPlanGenerator::generate(planInput(['weekStart' => CarbonImmutable::parse('2026-11-01')]));
    } finally {
        CarbonImmutable::setLocale('en');
    }

    expect($days[0]->date)->toBe('2026-10-26');
});
