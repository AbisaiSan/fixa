<?php

use App\Domain\Plan\DayProgress;
use App\Domain\Plan\DayStatus;

test('each day of the week gets its state (PLA-03, GAM-02)', function (string $date, bool $counted, bool $frozen, DayStatus $status) {
    expect(DayStatus::of($date, '2026-10-29', $counted, $frozen))->toBe($status);
})->with([
    'past studied' => ['2026-10-27', true, false, DayStatus::Done],
    'past missed' => ['2026-10-28', false, false, DayStatus::Missed],
    'past protected by a freeze' => ['2026-10-28', false, true, DayStatus::Frozen],
    'today still open' => ['2026-10-29', false, false, DayStatus::Today],
    'today with the goal met' => ['2026-10-29', true, false, DayStatus::Done],
    'tomorrow' => ['2026-10-30', false, false, DayStatus::Planned],
]);

test('a studied day is never shown as frozen', function () {
    expect(DayStatus::of('2026-10-28', '2026-10-29', counted: true, frozen: true))->toBe(DayStatus::Done);
});

test('the day counts when the done minutes reach the goal (GAM-01)', function () {
    expect(DayProgress::counted(goal: 10, planned: 10, done: 10, rush: false))->toBeTrue()
        ->and(DayProgress::counted(goal: 10, planned: 10, done: 7, rush: false))->toBeFalse()
        ->and(DayProgress::counted(goal: 10, planned: 14, done: 10, rush: false))->toBeTrue();
});

test('a lighter day counts when everything planned is done', function () {
    expect(DayProgress::counted(goal: 10, planned: 6, done: 6, rush: false))->toBeTrue()
        ->and(DayProgress::counted(goal: 10, planned: 6, done: 3, rush: false))->toBeFalse();
});

test('the rush mode alone counts the day (GAM-01, PLA-04)', function () {
    expect(DayProgress::counted(goal: 10, planned: 10, done: 0, rush: true))->toBeTrue()
        ->and(DayProgress::counted(goal: 10, planned: 0, done: 0, rush: true))->toBeTrue();
});

test('an empty day with nothing done does not count', function () {
    expect(DayProgress::counted(goal: 10, planned: 0, done: 0, rush: false))->toBeFalse();
});

test('the ring shows the done minutes against the goal, capped at 100 percent', function () {
    expect(DayProgress::percent(goal: 10, done: 4))->toBe(40)
        ->and(DayProgress::percent(goal: 10, done: 14))->toBe(100)
        ->and(DayProgress::percent(goal: 10, done: 0))->toBe(0);
});
