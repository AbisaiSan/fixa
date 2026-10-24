<?php

use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Progress\XpLedger;
use App\Domain\Progress\XpSource;
use App\Models\PlanDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    app(GenerateWeeklyPlan::class)->for($this->user);
    $this->actingAs($this->user);
});

test('a new student sees the friendly empty progress', function () {
    $this->get(route('progress'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('app/Progress')->where('status', 'empty'));
});

test('progress shows streak, freezes, weekly goal, level and history (J5-F5, GAM-04)', function () {
    PlanDay::forUser($this->user)->whereIn('date', ['2026-10-26', '2026-10-27'])->update(['counted_at' => now()]);
    app(XpLedger::class)->award($this->user, XpSource::Attempt, 1, 410);
    // Quarta: segunda e terça estudadas, quarta ainda aberta.
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'UTC'));

    $this->get(route('progress'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('streak.current', 2)
            ->where('streak.longest', 2)
            ->where('streak.freezes', 1)
            ->where('week.studied', 2)
            ->where('week.goal', 5)
            ->has('week.days', 7)
            ->where('week.days.0.studied', true)
            ->where('week.days.2.status', 'today')
            ->where('level.level', 2)
            ->where('level.in_level', 10)
            ->where('level.to_next', 390)
            ->has('history', 5)
            ->where('history.4.start', '2026-10-26')
            ->where('history.4.days', 2)
            ->missing('ranking'));
});

test('the progress of one student never shows another', function () {
    app(XpLedger::class)->award($this->user, XpSource::Attempt, 1, 50);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('progress'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'empty'));
});
