<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Plan\RecalculatePlan;
use App\Domain\Plan\TaskKind;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->recalculator = app(RecalculatePlan::class);
    $this->student = function (string $timezone = 'America/Sao_Paulo'): User {
        $user = User::factory()->create();
        $user->activeEnrollment->forceFill(['timezone' => $timezone])->save();
        $lesson = Lesson::factory()->forUser($user)->status(LessonStatus::Ready)->create();
        Card::factory()->forLesson($lesson)->create();
        foreach ([SessionKind::Writing, SessionKind::Reading] as $kind) {
            (new PracticeSession)->forceFill(['user_id' => $user->id, 'source_lesson_id' => $lesson->id, 'kind' => $kind])->save();
        }

        return $user->fresh();
    };
    // Plano gerado na segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = ($this->student)();
    app(GenerateWeeklyPlan::class)->for($this->user);

    $this->day = fn (User $user, string $date): PlanDay => PlanDay::forUser($user)->whereDate('date', $date)->firstOrFail();
    $this->complete = function (User $user, string $date): void {
        PlanTask::forUser($user)->where('plan_day_id', ($this->day)($user, $date)->id)->update(['completed_at' => now()]);
    };
    $this->planned = fn (User $user, string $date): int => (int) ($this->day)($user, $date)->tasks()->sum('minutes');
});

test('missing 1 day moves its lesson activity to a later day, within 1.5 times the goal (PLA-03)', function () {
    ($this->complete)($this->user, '2026-10-26');
    ($this->complete)($this->user, '2026-10-27');
    // Faltou quarta, 28. Quinta de manhã em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 11:00:00', 'UTC'));

    expect($this->recalculator->for($this->user))->toBeTrue();

    $moved = PlanTask::forUser($this->user)->whereNotNull('moved_from')->get();

    expect($moved)->toHaveCount(1)
        ->and($moved[0]->kind)->toBe(TaskKind::Lesson)
        ->and($moved[0]->moved_from->toDateString())->toBe('2026-10-28')
        // Hoje fica como estava; a tarefa vai para um dos dias seguintes.
        ->and($this->planned->__invoke($this->user, '2026-10-29'))->toBe(10);

    foreach (['2026-10-30', '2026-10-31', '2026-11-01'] as $date) {
        expect(($this->planned)($this->user, $date))->toBeLessThanOrEqual(15);
    }

    // As tarefas de quarta saíram da fila de atrasadas: nada vira dívida.
    expect(PlanTask::forUser($this->user)->where('plan_day_id', ($this->day)($this->user, '2026-10-28')->id)->whereNull('rescheduled_at')->count())->toBe(0);
});

test('missing 2 days spreads the work and never passes 1.5 times the goal (PLA-03)', function () {
    ($this->complete)($this->user, '2026-10-26');
    // Faltou terça e quarta.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 11:00:00', 'UTC'));

    $this->recalculator->for($this->user);

    $moved = PlanTask::forUser($this->user)->whereNotNull('moved_from')->get();

    expect($moved)->toHaveCount(2)
        ->and($moved->pluck('moved_from')->map->toDateString()->sort()->values()->all())->toBe(['2026-10-27', '2026-10-28'])
        ->and($moved->pluck('plan_day_id')->unique())->toHaveCount(2);

    foreach (['2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01'] as $date) {
        expect(($this->planned)($this->user, $date))->toBeLessThanOrEqual(15);
    }
});

test('recalculating twice on the same day changes nothing (idempotent)', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-29 11:00:00', 'UTC'));

    $this->recalculator->for($this->user);
    $tasks = PlanTask::forUser($this->user)->count();
    $this->travel(5)->hours();

    expect($this->recalculator->for($this->user))->toBeFalse()
        ->and(PlanTask::forUser($this->user)->count())->toBe($tasks);
});

test('the next day recalculates again only what was not handled yet', function () {
    ($this->complete)($this->user, '2026-10-26');
    ($this->complete)($this->user, '2026-10-27');
    $this->travelTo(CarbonImmutable::parse('2026-10-29 11:00:00', 'UTC'));
    $this->recalculator->for($this->user);
    $after = PlanTask::forUser($this->user)->whereNotNull('moved_from')->count();

    // Quinta também passou sem estudo.
    $this->travelTo(CarbonImmutable::parse('2026-10-30 11:00:00', 'UTC'));

    expect($this->recalculator->for($this->user))->toBeTrue()
        ->and(PlanTask::forUser($this->user)->whereNotNull('moved_from')->whereDate('moved_from', '2026-10-28')->count())->toBe($after);

    foreach (['2026-10-30', '2026-10-31', '2026-11-01'] as $date) {
        expect(($this->planned)($this->user, $date))->toBeLessThanOrEqual(15);
    }
});

test('the day turns at midnight in the student timezone (GAM-06)', function () {
    ($this->complete)($this->user, '2026-10-26');
    ($this->complete)($this->user, '2026-10-27');
    // 29/10 às 2h UTC: ainda quarta, 23h, em São Paulo. Quarta não passou.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 02:00:00', 'UTC'));
    $this->recalculator->for($this->user);

    expect(PlanTask::forUser($this->user)->whereNotNull('moved_from')->count())->toBe(0);

    // 3h UTC: quinta, 0h, em São Paulo. Agora quarta ficou para trás.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 03:00:00', 'UTC'));
    $this->recalculator->for($this->user);

    expect(PlanTask::forUser($this->user)->whereNotNull('moved_from')->count())->toBe(1);
});

test('on sunday there is no day left and missed tasks are dropped, not carried', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 12:00:00', 'UTC'));

    $this->recalculator->for($this->user);

    expect(PlanTask::forUser($this->user)->whereNotNull('moved_from')->count())->toBe(0)
        ->and(PlanTask::forUser($this->user)->whereNull('completed_at')->whereNull('rescheduled_at')
            ->whereIn('plan_day_id', PlanDay::forUser($this->user)->whereDate('date', '<', '2026-11-01')->pluck('id'))->count())->toBe(0);
});

test('one student recalculation never touches another student plan', function () {
    $other = ($this->student)();
    app(GenerateWeeklyPlan::class)->for($other);
    $before = PlanTask::forUser($other)->get()->toArray();
    $this->travelTo(CarbonImmutable::parse('2026-10-29 11:00:00', 'UTC'));

    $this->recalculator->for($this->user);

    expect(PlanTask::forUser($other)->get()->toArray())->toBe($before);
});
