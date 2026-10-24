<?php

use App\Domain\Plan\DayStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Progress\SettleStreak;
use App\Models\PlanDay;
use App\Models\Streak;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // Matrícula na segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    app(GenerateWeeklyPlan::class)->for($this->user);
    $this->settle = fn () => app(SettleStreak::class)->for($this->user);
    $this->count = function (string ...$dates): void {
        PlanDay::forUser($this->user)->whereIn('date', $dates)->update(['counted_at' => now()]);
    };
});

test('studied days build the streak once each day is over (GAM-01)', function () {
    ($this->count)('2026-10-26', '2026-10-27');
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'UTC'));

    $streak = ($this->settle)();

    expect($streak->current)->toBe(2)
        ->and($streak->freezes)->toBe(1)
        ->and($streak->settled_through->toDateString())->toBe('2026-10-27');
});

test('a missed day is protected by a freeze automatically and shown as protected (GAM-02)', function () {
    ($this->count)('2026-10-26', '2026-10-28');
    $this->travelTo(CarbonImmutable::parse('2026-10-29 12:00:00', 'UTC'));

    $streak = ($this->settle)();
    $tuesday = PlanDay::forUser($this->user)->whereDate('date', '2026-10-27')->sole();

    expect($streak->current)->toBe(2)
        ->and($streak->freezes)->toBe(0)
        ->and($tuesday->frozen_at)->not->toBeNull()
        ->and($tuesday->status('2026-10-29'))->toBe(DayStatus::Frozen);
});

test('the balance never passes 2 freezes, even after weeks away (GAM-02)', function () {
    ($this->count)('2026-10-26');
    // Quatro semanas depois.
    $this->travelTo(CarbonImmutable::parse('2026-11-25 12:00:00', 'UTC'));

    $streak = ($this->settle)();

    expect($streak->freezes)->toBeLessThanOrEqual(2)
        ->and($streak->current)->toBe(0)
        ->and($streak->longest)->toBe(1);
});

test('settling twice on the same day changes nothing', function () {
    ($this->count)('2026-10-26');
    $this->travelTo(CarbonImmutable::parse('2026-10-27 12:00:00', 'UTC'));
    $first = ($this->settle)()->toArray();
    $this->travel(3)->hours();

    expect(($this->settle)()->toArray())->toEqual($first);
});

test('the day closes at midnight in the student timezone (GAM-06)', function () {
    ($this->count)('2026-10-26');
    // 27/10 às 2h UTC: ainda segunda, 23h, em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 02:00:00', 'UTC'));
    expect(($this->settle)()->current)->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2026-10-27 03:00:00', 'UTC'));
    expect(($this->settle)()->current)->toBe(1);
});

test('the hourly command settles every student with nobody logged in', function () {
    ($this->count)('2026-10-26');
    $this->travelTo(CarbonImmutable::parse('2026-10-27 12:00:00', 'UTC'));

    $this->artisan('fixa:plan-week')->assertSuccessful();

    expect(Streak::forUser($this->user)->sole()->current)->toBe(1);
});
