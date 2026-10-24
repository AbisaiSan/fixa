<?php

use App\Domain\Plan\RushRule;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->start = CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC');
});

test('a rush with answers that ran the 5 minutes counts (PLA-04)', function () {
    expect(RushRule::completes($this->start, $this->start->addMinutes(5), reviews: 9, dueLeft: 4))->toBeTrue();
});

test('a rush that cleared the due cards before 5 minutes counts', function () {
    expect(RushRule::completes($this->start, $this->start->addMinutes(2), reviews: 6, dueLeft: 0))->toBeTrue();
});

test('a rush stopped early with due cards left does not count', function () {
    expect(RushRule::completes($this->start, $this->start->addMinutes(2), reviews: 6, dueLeft: 3))->toBeFalse();
});

test('a rush without any answer never counts', function () {
    expect(RushRule::completes($this->start, $this->start->addMinutes(6), reviews: 0, dueLeft: 0))->toBeFalse();
});

test('a rush that was never started does not count', function () {
    expect(RushRule::completes(null, $this->start, reviews: 5, dueLeft: 0))->toBeFalse();
});

test('a few seconds of clock drift are tolerated', function () {
    expect(RushRule::completes($this->start, $this->start->addSeconds(5 * 60 - 10), reviews: 3, dueLeft: 2))->toBeTrue();
});
