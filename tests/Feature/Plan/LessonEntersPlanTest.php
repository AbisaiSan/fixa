<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Plan\LessonActivity;
use App\Domain\Plan\TaskKind;
use App\Jobs\ProcessLesson;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\AiFixtures;

beforeEach(function () {
    // Respostas para duas aulas: o falso consome uma por chamada.
    app(FakeAiProvider::class)
        ->respondWith(AiTask::LessonReview, AiFixtures::lessonReview(), AiFixtures::lessonReview())
        ->respondWith(AiTask::Cards, AiFixtures::cards(), AiFixtures::cards())
        ->respondWith(AiTask::Activities, AiFixtures::activities(), AiFixtures::activities())
        ->respondWith(AiTask::Reading, AiFixtures::reading(), AiFixtures::reading());
    // Quarta, 28/10, 15h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-28 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->process = function (string $text = 'I have worked here since 2022. '): Lesson {
        $lesson = Lesson::factory()->forUser($this->user)->create();
        $lesson->forceFill(['extracted_text' => str_repeat($text, 12)])->save();
        app()->call([new ProcessLesson($lesson->id), 'handle']);

        return $lesson->fresh();
    };
    $this->lessonTasks = fn (string $date) => PlanTask::forUser($this->user)
        ->where('plan_day_id', PlanDay::forUser($this->user)->whereDate('date', $date)->value('id'))
        ->where('kind', TaskKind::Lesson)
        ->get();
});

test('the first lesson of a new student enters today plan (PLA-05)', function () {
    app(GenerateWeeklyPlan::class)->for($this->user);
    expect(PlanTask::forUser($this->user)->count())->toBe(0);

    $lesson = ($this->process)();

    $today = PlanDay::forUser($this->user)->whereDate('date', '2026-10-28')->sole();
    $tasks = $today->tasks()->get();

    expect($lesson->status)->toBe(LessonStatus::Ready)
        ->and($tasks->pluck('kind')->all())->toBe([TaskKind::Flashcards, TaskKind::Lesson, TaskKind::WeakPoint])
        ->and($tasks[1]->source_lesson_id)->toBe($lesson->id)
        ->and($tasks->sum('minutes'))->toBe(10)
        // Os dias que já passaram não mudam.
        ->and(PlanTask::forUser($this->user)->whereIn('plan_day_id', PlanDay::forUser($this->user)->whereDate('date', '<', '2026-10-28')->pluck('id'))->count())->toBe(0);
});

test('a lesson sent without a plan creates the week with it', function () {
    $lesson = ($this->process)();

    expect(PlanDay::forUser($this->user)->count())->toBe(5)
        ->and(($this->lessonTasks)('2026-10-28')->first()->source_lesson_id)->toBe($lesson->id);
});

test('a new lesson replaces the pending lesson blocks from today on, keeping what was done', function () {
    $old = ($this->process)();
    // Hoje o aluno já fez os flashcards e a atividade da aula antiga.
    PlanTask::forUser($this->user)
        ->where('plan_day_id', PlanDay::forUser($this->user)->whereDate('date', '2026-10-28')->value('id'))
        ->whereIn('kind', [TaskKind::Flashcards, TaskKind::Lesson])
        ->update(['completed_at' => now()]);
    $this->travel(2)->hours();

    $new = ($this->process)();

    $today = PlanDay::forUser($this->user)->whereDate('date', '2026-10-28')->sole()->tasks()->get();

    expect($today)->toHaveCount(3)
        ->and($today->pluck('position')->all())->toBe([1, 2, 3])
        ->and($today[1]->source_lesson_id)->toBe($old->id)
        ->and($today[1]->completed_at)->not->toBeNull()
        ->and(($this->lessonTasks)('2026-10-29')->first()->source_lesson_id)->toBe($new->id)
        ->and(($this->lessonTasks)('2026-11-01')->first()->source_lesson_id)->toBe($new->id);
});

test('a moved task stays in place when a new lesson arrives', function () {
    ($this->process)();
    $friday = PlanDay::forUser($this->user)->whereDate('date', '2026-10-30')->sole();
    (new PlanTask)->forceFill([
        'user_id' => $this->user->id, 'plan_day_id' => $friday->id, 'position' => 4,
        'kind' => TaskKind::Lesson, 'activity' => LessonActivity::Reading, 'minutes' => 4,
        'important' => true, 'moved_from' => '2026-10-27',
    ])->save();

    ($this->process)();

    $tasks = $friday->tasks()->get();

    expect($tasks)->toHaveCount(4)
        ->and($tasks->last()->moved_from?->toDateString())->toBe('2026-10-27')
        ->and($tasks->pluck('position')->all())->toBe([1, 2, 3, 4]);
});

test('a failed lesson does not touch the plan', function () {
    app(GenerateWeeklyPlan::class)->for($this->user);
    Card::factory()->create(['user_id' => $this->user->id]);

    $lesson = ($this->process)('curto');

    expect($lesson->status)->toBe(LessonStatus::Failed)
        ->and($lesson->failure_reason)->toBe('too_short')
        ->and(PlanTask::forUser($this->user)->count())->toBe(0);
});
