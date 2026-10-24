<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Plan\LessonActivity;
use App\Domain\Plan\TaskKind;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;

beforeEach(function () {
    $this->generator = app(GenerateWeeklyPlan::class);
    $this->student = function (string $timezone = 'America/Sao_Paulo', int $goal = 10): User {
        $user = User::factory()->create();
        $user->activeEnrollment->forceFill(['timezone' => $timezone, 'daily_goal_minutes' => $goal])->save();

        $lesson = Lesson::factory()->forUser($user)->status(LessonStatus::Ready)->create();
        Card::factory()->forLesson($lesson)->create();
        (new PracticeSession)->forceFill([
            'user_id' => $user->id, 'source_lesson_id' => $lesson->id, 'kind' => SessionKind::Writing,
        ])->save();

        return $user->fresh();
    };
});

test('a monday generates the whole week with the 3 blocks in the goal (PLA-01, PLA-02)', function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)();

    expect($this->generator->for($user))->toBeTrue();

    $days = PlanDay::forUser($user)->orderBy('date')->get();
    $tasks = $days->first()->tasks()->get();

    expect($days)->toHaveCount(7)
        ->and($days->first()->date->toDateString())->toBe('2026-10-26')
        ->and($days->last()->date->toDateString())->toBe('2026-11-01')
        ->and($days->first()->goal_minutes)->toBe(10)
        ->and($tasks->pluck('kind')->all())->toBe([TaskKind::Flashcards, TaskKind::Lesson, TaskKind::WeakPoint])
        ->and($tasks->sum('minutes'))->toBe(10)
        ->and($tasks[1]->activity)->toBe(LessonActivity::Writing)
        ->and($tasks[1]->source_lesson_id)->toBe(Lesson::forUser($user)->value('id'))
        // Escrita e vocabulário prontos: o rodízio alterna os dois.
        ->and($days[1]->tasks()->get()[1]->activity)->toBe(LessonActivity::Vocabulary);
});

test('generating twice keeps a single plan for the week', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)();

    $this->generator->for($user);
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'UTC'));

    expect($this->generator->for($user))->toBeFalse()
        ->and(PlanDay::forUser($user)->count())->toBe(7);
});

test('the week turns at midnight in the student timezone, not in UTC (GAM-06)', function () {
    // 26/10 às 2h UTC: ainda domingo, 23h, em São Paulo; já segunda, 2h, em Lisboa.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 02:00:00', 'UTC'));
    $saoPaulo = ($this->student)('America/Sao_Paulo');
    $lisbon = ($this->student)('Europe/Lisbon');

    $this->generator->for($saoPaulo);
    $this->generator->for($lisbon);

    expect(PlanDay::forUser($saoPaulo)->orderBy('date')->pluck('date')->map->toDateString()->all())->toBe(['2026-10-25'])
        ->and(PlanDay::forUser($saoPaulo)->first()->week_start->toDateString())->toBe('2026-10-19')
        ->and(PlanDay::forUser($lisbon)->count())->toBe(7)
        ->and(PlanDay::forUser($lisbon)->min('date'))->toStartWith('2026-10-26');
});

test('a student who starts on thursday gets thursday to sunday', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-29 15:00:00', 'UTC'));
    $user = ($this->student)();

    $this->generator->for($user);

    expect(PlanDay::forUser($user)->orderBy('date')->pluck('date')->map->toDateString()->all())
        ->toBe(['2026-10-29', '2026-10-30', '2026-10-31', '2026-11-01'])
        ->and(PlanDay::forUser($user)->first()->week_start->toDateString())->toBe('2026-10-26');
});

test('the latest ready lesson is the one planned, and a failed one is ignored', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)();
    $this->travel(1)->minute();
    Lesson::factory()->forUser($user)->status(LessonStatus::Failed)->create();

    $this->generator->for($user);

    $lessonIds = PlanTask::forUser($user)->where('kind', TaskKind::Lesson)->distinct()->pluck('source_lesson_id')->all();

    expect($lessonIds)->toBe([Lesson::forUser($user)->where('status', LessonStatus::Ready)->value('id')]);
});

test('a new student without lessons or cards gets empty days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = User::factory()->create();

    $this->generator->for($user);

    expect(PlanDay::forUser($user)->count())->toBe(7)
        ->and(PlanTask::forUser($user)->count())->toBe(0);
});

test('the hourly command plans for everyone without a plan, with nobody logged in', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $first = ($this->student)();
    $second = ($this->student)('Europe/Lisbon', 20);
    $this->generator->for($first);

    $this->artisan('fixa:plan-week')->expectsOutput('Planos criados: 1. Dias recalculados: 2.')->assertSuccessful();

    expect(PlanDay::forUser($second)->count())->toBe(7)
        ->and(PlanDay::forUser($second)->first()->tasks()->sum('minutes'))->toBe(20)
        // O plano de um aluno nunca carrega a aula de outro.
        ->and(PlanTask::forUser($second)->whereNotNull('source_lesson_id')->pluck('source_lesson_id')->unique()->all())
        ->toBe([Lesson::forUser($second)->value('id')]);
});

test('the plan command is scheduled every hour', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'fixa:plan-week'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});
