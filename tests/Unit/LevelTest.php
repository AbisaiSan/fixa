<?php

use App\Domain\Progress\Level;
use App\Domain\Progress\WeeklyGoal;

test('every 400 XP is a new level, with the bar to the next one (GAM-04)', function (int $xp, int $level, int $inLevel, int $toNext, int $percent) {
    $result = Level::of($xp);

    expect($result->level)->toBe($level)
        ->and($result->inLevel)->toBe($inLevel)
        ->and($result->toNext)->toBe($toNext)
        ->and($result->percent)->toBe($percent);
})->with([
    'start' => [0, 1, 0, 400, 0],
    'middle of the first' => [200, 1, 200, 200, 50],
    'exactly the second' => [400, 2, 0, 400, 0],
    'prototype' => [2740, 7, 340, 60, 85],
]);

test('negative XP is treated as zero', function () {
    expect(Level::of(-50)->level)->toBe(1);
});

test('the level only goes up when the XP goes up', function () {
    $previous = Level::of(0);

    foreach (range(1, 2000, 7) as $xp) {
        $current = Level::of($xp);
        expect($current->level)->toBeGreaterThanOrEqual($previous->level);
        $previous = $current;
    }
});

test('the weekly goal is 5 studied days, capped at 100 percent (GAM-04)', function () {
    expect(WeeklyGoal::DAYS)->toBe(5)
        ->and(WeeklyGoal::percent(3))->toBe(60)
        ->and(WeeklyGoal::percent(7))->toBe(100)
        ->and(WeeklyGoal::reached(5))->toBeTrue()
        ->and(WeeklyGoal::reached(4))->toBeFalse();
});
