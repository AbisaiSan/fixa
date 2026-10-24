<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\DayStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
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

beforeEach(function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create();
    $this->cards = Card::factory()->count(12)->forLesson($this->lesson)->sequence(fn ($sequence) => ['front' => "word {$sequence->index}"])->create();
    $this->session = function (SessionKind $kind): PracticeSession {
        $session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $kind === SessionKind::WeakPoint ? null : $this->lesson->id, 'kind' => $kind]);
        $session->save();
        (new PracticeItem)->forceFill([
            'user_id' => $this->user->id, 'practice_session_id' => $session->id, 'position' => 1, 'type' => ItemType::Choice,
            'content' => ['instruction' => 'Escolha.', 'sentence' => 'I have lived here ___ 2019.', 'options' => ['since', 'for'], 'answer' => 0, 'accepted' => ['since'], 'corrected' => 'since', 'why' => 'Since marca o início.'],
        ])->save();

        return $session;
    };
    $this->writing = ($this->session)(SessionKind::Writing);
    app(GenerateWeeklyPlan::class)->for($this->user->fresh());
    $this->today = fn (): PlanDay => PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->sole();
    $this->task = fn (TaskKind $kind): PlanTask => ($this->today)()->tasks()->get()->firstWhere('kind', $kind);
    $this->actingAs($this->user);
});

test('finishing the writing session closes the lesson block of today (J5-B4)', function () {
    $item = $this->writing->items()->first();

    $this->postJson(route('practice.attempts.store', $item), ['answer' => 'since'])->assertOk();

    expect(($this->task)(TaskKind::Lesson)->completed_at)->not->toBeNull()
        ->and(($this->task)(TaskKind::Flashcards)->completed_at)->toBeNull()
        ->and(($this->today)()->counted_at)->toBeNull()
        ->and(($this->today)()->status('2026-10-26'))->toBe(DayStatus::Today);
});

test('the flashcards block closes after 3 cards per planned minute', function () {
    $needed = ($this->task)(TaskKind::Flashcards)->minutes * 3;

    foreach ($this->cards->take($needed - 1) as $card) {
        $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
    }
    expect(($this->task)(TaskKind::Flashcards)->completed_at)->toBeNull();

    $this->postJson(route('cards.reviews.store', $this->cards[$needed - 1]), ['rating' => 3])->assertOk();

    expect(($this->task)(TaskKind::Flashcards)->completed_at)->not->toBeNull();
});

test('the flashcards block closes when the day queue is empty', function () {
    $this->cards->skip(2)->each->forceFill(['suspended_at' => now()])->each->save();

    foreach ($this->cards->take(2) as $card) {
        $this->postJson(route('cards.reviews.store', $card), ['rating' => 4])->assertOk();
    }

    expect(($this->task)(TaskKind::Flashcards)->completed_at)->not->toBeNull();
});

test('doing every block counts the day and shows it as done (GAM-01)', function () {
    foreach ($this->cards as $card) {
        $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
    }
    $this->postJson(route('practice.attempts.store', $this->writing->items()->first()), ['answer' => 'since'])->assertOk();
    expect(($this->today)()->counted_at)->toBeNull();

    $weak = ($this->session)(SessionKind::WeakPoint);
    $this->postJson(route('practice.attempts.store', $weak->items()->first()), ['answer' => 'since'])->assertOk();

    expect(($this->today)()->counted_at)->not->toBeNull()
        ->and(($this->today)()->status('2026-10-26'))->toBe(DayStatus::Done);
});

test('a day left behind without study is missed, the next ones are planned', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 12:00:00', 'UTC'));

    $days = PlanDay::forUser($this->user)->orderBy('date')->get();

    expect($days[0]->status('2026-10-27'))->toBe(DayStatus::Missed)
        ->and($days[1]->status('2026-10-27'))->toBe(DayStatus::Today)
        ->and($days[2]->status('2026-10-27'))->toBe(DayStatus::Planned);
});

test('a completed writing session is not planned again the next week', function () {
    $this->postJson(route('practice.attempts.store', $this->writing->items()->first()), ['answer' => 'since'])->assertOk();
    $this->travelTo(CarbonImmutable::parse('2026-11-02 12:00:00', 'UTC'));

    app(GenerateWeeklyPlan::class)->for($this->user->fresh());

    $activities = PlanTask::forUser($this->user)
        ->whereIn('plan_day_id', PlanDay::forUser($this->user)->whereDate('week_start', '2026-11-02')->pluck('id'))
        ->where('kind', TaskKind::Lesson)
        ->pluck('activity')
        ->map->value
        ->unique()
        ->values()
        ->all();

    expect($activities)->toBe(['vocabulary']);
});
