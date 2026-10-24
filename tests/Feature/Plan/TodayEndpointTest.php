<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\TaskKind;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create(['title' => 'Present Perfect']);
    Card::factory()->count(4)->forLesson($this->lesson)
        ->sequence(fn ($sequence) => ['front' => "word {$sequence->index}"])
        ->create();
    $this->writing = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $this->lesson->id, 'kind' => SessionKind::Writing]);
    $this->writing->save();
    (new PracticeItem)->forceFill([
        'user_id' => $this->user->id, 'practice_session_id' => $this->writing->id, 'position' => 1, 'type' => ItemType::Choice,
        'content' => ['instruction' => 'Escolha.', 'sentence' => 'I have lived here ___ 2019.', 'options' => ['since', 'for'], 'answer' => 0, 'accepted' => ['since'], 'corrected' => 'since', 'why' => 'Since marca o início.'],
    ])->save();
    $this->actingAs($this->user);
});

test('today shows the plan with the next block one click away (PLA-06)', function () {
    $this->get(route('today'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Today')
            ->where('plan.date', '2026-10-26')
            ->where('plan.goal.minutes', 10)
            ->where('plan.goal.done', 0)
            ->where('plan.goal.percent', 0)
            ->where('plan.blocks.0.kind', 'flashcards')
            ->where('plan.blocks.0.href', route('flashcards'))
            ->where('plan.blocks.0.count', 4)
            ->where('plan.blocks.1.kind', 'lesson')
            ->where('plan.blocks.1.activity', 'writing')
            ->where('plan.blocks.1.href', route('practice.writing', $this->writing))
            ->where('plan.blocks.1.lesson.title', 'Present Perfect')
            ->where('plan.next', fn (string $ulid) => $ulid === PlanTask::forUser($this->user)->where('kind', TaskKind::Flashcards)->value('ulid'))
            ->where('plan.streak.current', 0)
            ->where('plan.streak.freezes', 1)
            ->where('plan.streak.max_freezes', 2)
            ->where('plan.xp.level', 1)
            ->where('plan.resume', null));
});

test('a weak point with nothing back today is set aside and does not weigh on the goal', function () {
    $this->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plan.blocks.2.kind', 'weak_point')
            ->where('plan.blocks.2.skipped', true)
            ->where('plan.blocks.2.href', null)
            ->where('plan.goal.planned', 7));
});

test('the ring follows the done blocks and the next block moves on', function () {
    $this->postJson(route('practice.attempts.store', $this->writing->items()->first()), ['answer' => 'since'])->assertOk();

    $this->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plan.blocks.1.done', true)
            ->where('plan.goal.done', 4)
            ->where('plan.goal.percent', 40)
            ->where('plan.xp.total', 14)
            ->where('plan.next', PlanTask::forUser($this->user)->where('kind', TaskKind::Flashcards)->value('ulid')));
});

test('coming back after missing days shows the welcome back message, never a count of days (GAM-05)', function () {
    $this->get(route('today'));
    PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->update(['counted_at' => now()]);
    // Volta na quinta, depois de faltar terça e quarta.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 12:00:00', 'UTC'));

    $response = $this->get(route('today'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('plan.resume', 'welcome_back')
        // Terça ficou protegida pelo congelamento; quarta, sem outro, zerou.
        ->where('plan.streak.current', 0)
        ->where('plan.streak.freezes', 0)
        ->missing('plan.missed'));
    expect(json_encode($response->viewData('page')['props']['plan']))->not->toContain('missed');
});

test('the lesson block moves on to the reading when the writing was done another day', function () {
    $reading = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $this->lesson->id, 'kind' => SessionKind::Reading]);
    $reading->save();
    (new PracticeItem)->forceFill([
        'user_id' => $this->user->id, 'practice_session_id' => $reading->id, 'position' => 1, 'type' => ItemType::Choice,
        'content' => ['instruction' => 'Escolha.', 'sentence' => 'Ana has worked there ___ 2020.', 'options' => ['since', 'for'], 'answer' => 0, 'accepted' => ['since'], 'corrected' => 'since', 'why' => 'Since.'],
    ])->save();
    $this->get(route('today'));
    $this->postJson(route('practice.attempts.store', $this->writing->items()->first()), ['answer' => 'since'])->assertOk();
    // Quinta, de novo escrita no rodízio, mas ela já foi feita.
    $this->travelTo(CarbonImmutable::parse('2026-10-29 12:00:00', 'UTC'));
    PlanTask::forUser($this->user)
        ->where('plan_day_id', PlanDay::forUser($this->user)->whereDate('date', '2026-10-29')->value('id'))
        ->where('kind', TaskKind::Lesson)
        ->update(['activity' => 'writing']);

    $this->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plan.blocks.1.activity', 'reading')
            ->where('plan.blocks.1.href', route('practice.reading', $reading)));
});

test('today never shows another student plan or lesson', function () {
    $other = User::factory()->create();
    $this->get(route('today'));

    $this->actingAs($other)->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'empty')
            ->where('plan.blocks', [])
            ->where('plan.next', null));
});
