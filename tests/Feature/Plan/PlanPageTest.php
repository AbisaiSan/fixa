<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create();
    Card::factory()->forLesson($lesson)->create();
    foreach ([SessionKind::Writing, SessionKind::Reading] as $kind) {
        (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $lesson->id, 'kind' => $kind])->save();
    }
    app(GenerateWeeklyPlan::class)->for($this->user->fresh());
    $this->actingAs($this->user);
});

test('the plan page shows the 7 days with their states (J5-F4)', function () {
    PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->update(['counted_at' => now()]);
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'UTC'));

    $this->get(route('plan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Plan')
            ->where('today', '2026-10-28')
            ->where('weekStart', '2026-10-26')
            ->where('goal', 10)
            ->has('days', 7)
            ->where('days.0.status', 'done')
            // Terça sem estudo: o congelamento protegeu.
            ->where('days.1.status', 'frozen')
            ->where('days.2.status', 'today')
            ->where('days.3.status', 'planned')
            ->where('redone', true)
            ->has('days.2.tasks', 3));
});

test('the moved task shows where it came from', function () {
    PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->update(['counted_at' => now()]);
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00:00', 'UTC'));

    $this->get(route('plan'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days', fn ($days) => collect($days)->flatMap(fn ($day) => $day['tasks'])->contains(fn ($task) => $task['moved_from'] === '2026-10-27')));
});

test('a new daily goal applies from tomorrow and re-splits the following days (PLA-01)', function () {
    $this->patch(route('plan.goal'), ['daily_goal_minutes' => 20])->assertRedirect()->assertSessionHas('status', 'goal-updated');

    $today = PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->sole();
    $tomorrow = PlanDay::forUser($this->user)->whereDate('date', '2026-10-27')->sole();

    expect($this->user->activeEnrollment->fresh()->daily_goal_minutes)->toBe(20)
        ->and($today->goal_minutes)->toBe(10)
        ->and((int) $today->tasks()->sum('minutes'))->toBe(10)
        ->and($tomorrow->goal_minutes)->toBe(20)
        ->and((int) $tomorrow->tasks()->sum('minutes'))->toBe(20);
});

test('the daily goal only accepts 5 to 30 minutes in steps of 5', function (mixed $goal) {
    $this->patch(route('plan.goal'), ['daily_goal_minutes' => $goal])->assertSessionHasErrors('daily_goal_minutes');

    expect($this->user->activeEnrollment->fresh()->daily_goal_minutes)->toBe(10);
})->with([0, 4, 31, 12, 'dez']);

test('the plan of one student never shows another', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('plan'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.tasks', [])
            ->where('redone', false));

    expect(PlanTask::forUser($other)->count())->toBe(0);
});

test('guests cannot see or change the plan', function () {
    auth()->logout();

    $this->get(route('plan'))->assertRedirect(route('login'));
    $this->patch(route('plan.goal'), ['daily_goal_minutes' => 20])->assertRedirect(route('login'));
});
