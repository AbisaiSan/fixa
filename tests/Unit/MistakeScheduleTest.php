<?php

use App\Domain\Practice\MistakeSchedule;
use App\Domain\Practice\MistakeState;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->tz = 'America/Sao_Paulo';
    // 15h de 24/10 em São Paulo.
    $this->now = CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC');
});

function day(string $date): string
{
    // 0h em São Paulo é 3h UTC.
    return CarbonImmutable::parse("{$date} 03:00:00", 'UTC')->toIso8601String();
}

test('a new error comes back the next day (ERR-02)', function () {
    $state = MistakeSchedule::afterError(MistakeState::new(), $this->now, $this->tz);

    expect($state->repetitions)->toBe(1)
        ->and($state->priority)->toBeFalse()
        ->and($state->dueAt?->toIso8601String())->toBe(day('2026-10-25'));
});

test('correct answers on the point push it to 3 and then 7 days, and the third one takes it out (ERR-02, ERR-03)', function () {
    $state = MistakeSchedule::afterError(MistakeState::new(), $this->now, $this->tz);

    $state = MistakeSchedule::afterCorrect($state, $this->now->addDay(), $this->tz);
    expect($state->correctStreak)->toBe(1)
        ->and($state->dueAt?->toIso8601String())->toBe(day('2026-10-28'))
        ->and($state->resolved)->toBeFalse();

    $state = MistakeSchedule::afterCorrect($state, $this->now->addDays(4), $this->tz);
    expect($state->correctStreak)->toBe(2)
        ->and($state->dueAt?->toIso8601String())->toBe(day('2026-11-04'));

    $state = MistakeSchedule::afterCorrect($state, $this->now->addDays(11), $this->tz);
    expect($state->resolved)->toBeTrue()
        ->and($state->dueAt)->toBeNull();
});

test('an error in the middle restarts the streak and the schedule', function () {
    $state = MistakeSchedule::afterError(MistakeState::new(), $this->now, $this->tz);
    $state = MistakeSchedule::afterCorrect($state, $this->now->addDay(), $this->tz);
    $state = MistakeSchedule::afterCorrect($state, $this->now->addDays(4), $this->tz);

    $state = MistakeSchedule::afterError($state, $this->now->addDays(11), $this->tz);

    expect($state->correctStreak)->toBe(0)
        ->and($state->repetitions)->toBe(2)
        ->and($state->dueAt?->toIso8601String())->toBe(day('2026-11-05'))
        ->and($state->resolved)->toBeFalse();
});

test('the third repetition gives the mistake priority', function () {
    $state = MistakeState::new();

    foreach (range(1, 3) as $time) {
        $state = MistakeSchedule::afterError($state, $this->now->addDays($time), $this->tz);
    }

    expect($state->repetitions)->toBe(3)->and($state->priority)->toBeTrue();
});

test('a mistake that comes back after leaving the list starts over', function () {
    $state = MistakeSchedule::afterError(MistakeState::new(), $this->now, $this->tz);

    foreach ([1, 4, 11] as $days) {
        $state = MistakeSchedule::afterCorrect($state, $this->now->addDays($days), $this->tz);
    }

    $state = MistakeSchedule::afterError($state, $this->now->addDays(30), $this->tz);

    expect($state->resolved)->toBeFalse()
        ->and($state->correctStreak)->toBe(0)
        ->and($state->dueAt?->toIso8601String())->toBe(day('2026-11-24'));
});

test('a late night error still counts the student day', function () {
    // 23h50 de 24/10 em São Paulo, já 25/10 em UTC.
    $late = CarbonImmutable::parse('2026-10-25 02:50:00', 'UTC');

    expect(MistakeSchedule::afterError(MistakeState::new(), $late, $this->tz)->dueAt?->toIso8601String())->toBe(day('2026-10-25'));
});
