<?php

use App\Domain\Progress\StreakRules;
use App\Domain\Progress\StreakState;

/**
 * Dias seguidos a partir de uma data, com true para estudado.
 *
 * @param  list<bool>  $counted
 * @return array<string, bool>
 */
function days(string $from, array $counted): array
{
    $days = [];
    foreach ($counted as $offset => $value) {
        $days[date('Y-m-d', strtotime("{$from} +{$offset} days"))] = $value;
    }

    return $days;
}

beforeEach(function () {
    // Começou na segunda, 26/10, com o congelamento da semana.
    $this->start = StreakState::start('2026-10-26');
});

test('a new student starts with no streak and the freeze of the week (GAM-02)', function () {
    expect($this->start->current)->toBe(0)
        ->and($this->start->freezes)->toBe(1)
        ->and($this->start->settledThrough)->toBe('2026-10-25');
});

test('each studied day adds one to the streak (GAM-01)', function () {
    $result = StreakRules::settle($this->start, days('2026-10-26', [true, true, true]));

    expect($result->state->current)->toBe(3)
        ->and($result->state->longest)->toBe(3)
        ->and($result->state->settledThrough)->toBe('2026-10-28')
        ->and($result->frozen)->toBe([]);
});

test('a missed day uses a freeze automatically and keeps the streak (GAM-02)', function () {
    $result = StreakRules::settle($this->start, days('2026-10-26', [true, true, false, true]));

    expect($result->state->current)->toBe(3)
        ->and($result->state->freezes)->toBe(0)
        ->and($result->frozen)->toBe(['2026-10-28']);
});

test('a missed day without freezes resets the streak but keeps the longest', function () {
    $result = StreakRules::settle($this->start, days('2026-10-26', [true, true, false, false, true]));

    expect($result->state->current)->toBe(1)
        ->and($result->state->longest)->toBe(2)
        ->and($result->frozen)->toBe(['2026-10-28']);
});

test('one freeze arrives every monday and the balance never passes 2 (GAM-02)', function () {
    // Três semanas inteiras estudando, sem gastar congelamento.
    $result = StreakRules::settle($this->start, days('2026-10-26', array_fill(0, 21, true)));

    expect($result->state->freezes)->toBe(2)
        ->and($result->state->current)->toBe(21);
});

test('two freezes protect two missed days in a row, the third breaks the streak', function () {
    $twoWeeks = StreakRules::settle($this->start, days('2026-10-26', array_fill(0, 7, true)));
    expect($twoWeeks->state->freezes)->toBe(1);

    // Segunda, 02/11, traz o segundo congelamento.
    $result = StreakRules::settle($twoWeeks->state, days('2026-11-02', [false, false, false]));

    expect($result->frozen)->toBe(['2026-11-02', '2026-11-03'])
        ->and($result->state->freezes)->toBe(0)
        ->and($result->state->current)->toBe(0)
        ->and($result->state->longest)->toBe(7);
});

test('a freeze is not spent when there is no streak to protect', function () {
    $result = StreakRules::settle($this->start, days('2026-10-26', [false, false]));

    expect($result->state->freezes)->toBe(1)
        ->and($result->frozen)->toBe([]);
});

test('days already settled are ignored, so settling again changes nothing', function () {
    $first = StreakRules::settle($this->start, days('2026-10-26', [true, false, true]));
    $again = StreakRules::settle($first->state, days('2026-10-26', [true, false, true]));

    expect($again->state)->toEqual($first->state)
        ->and($again->frozen)->toBe([]);
});

test('the streak shown today adds today once the goal is met', function () {
    $state = StreakRules::settle($this->start, days('2026-10-26', [true, true]))->state;

    expect(StreakRules::shown($state, todayCounted: false))->toBe(2)
        ->and(StreakRules::shown($state, todayCounted: true))->toBe(3);
});

test('the freeze balance stays between 0 and 2 for any sequence of days', function () {
    mt_srand(2026);

    foreach (range(1, 50) as $run) {
        $counted = array_map(fn () => mt_rand(0, 2) > 0, range(1, 60));
        $state = $this->start;

        foreach (array_chunk(days('2026-10-26', $counted), 5, true) as $chunk) {
            $state = StreakRules::settle($state, $chunk)->state;
            expect($state->freezes)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(2)
                ->and($state->longest)->toBeGreaterThanOrEqual($state->current);
        }
    }
});

test('on monday the new freeze is already shown before the day is settled', function () {
    $state = StreakRules::settle($this->start, days('2026-10-26', array_fill(0, 7, true)))->state;

    expect(StreakRules::shownFreezes($state, '2026-11-01'))->toBe(1)
        ->and(StreakRules::shownFreezes($state, '2026-11-02'))->toBe(2);
});
