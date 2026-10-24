<?php

use App\Domain\Cards\Scheduling\Rating;
use App\Domain\Lessons\DeleteLesson;
use App\Domain\Lessons\LessonStatus;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Domain\Practice\Verdict;
use App\Domain\Progress\XpLedger;
use App\Domain\Progress\XpSource;
use App\Models\Attempt;
use App\Models\Card;
use App\Models\CardReview;
use App\Models\Lesson;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use App\Models\XpEvent;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->ledger = app(XpLedger::class);
    $this->lesson = function (): Lesson {
        $lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create();
        Card::factory()->count(4)->forLesson($lesson)
            ->sequence(fn ($sequence) => ['front' => "word {$lesson->id} {$sequence->index}"])
            ->create();
        $session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $lesson->id, 'kind' => SessionKind::Writing]);
        $session->save();
        foreach ([1, 2] as $position) {
            (new PracticeItem)->forceFill([
                'user_id' => $this->user->id, 'practice_session_id' => $session->id, 'position' => $position, 'type' => ItemType::Choice,
                'content' => ['instruction' => 'Escolha.', 'sentence' => 'I have lived here ___ 2019.', 'options' => ['since', 'for'], 'answer' => 0, 'accepted' => ['since'], 'corrected' => 'since', 'why' => 'Since marca o início.'],
            ])->save();
        }

        return $lesson;
    };
    $this->actingAs($this->user);
});

test('a card answer gives 2 XP and a finished session its bonus (GAM-03)', function () {
    $lesson = ($this->lesson)();
    $card = Card::forUser($this->user)->where('source_lesson_id', $lesson->id)->first();

    $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
    expect($this->ledger->total($this->user))->toBe(2);

    $items = PracticeSession::forUser($this->user)->sole()->items()->get();
    $this->postJson(route('practice.attempts.store', $items[0]), ['answer' => 'since'])->assertOk();
    $this->postJson(route('practice.attempts.store', $items[1]), ['answer' => 'for'])->assertOk();

    // Escolha certa 4, errada 1, sessão concluída 10.
    expect($this->ledger->total($this->user))->toBe(2 + 4 + 1 + 10);
});

test('sending the same answer again never gives XP twice', function () {
    ($this->lesson)();
    $card = Card::forUser($this->user)->first();
    $id = fake()->uuid();

    $this->postJson(route('cards.reviews.store', $card), ['rating' => 3, 'client_review_id' => $id])->assertOk();
    $this->postJson(route('cards.reviews.store', $card), ['rating' => 3, 'client_review_id' => $id])->assertOk();

    expect($this->ledger->total($this->user))->toBe(2);
});

test('the ledger is rebuilt from card reviews and attempts without losing or doubling anything (GAM-03)', function () {
    $lesson = ($this->lesson)();
    $card = Card::forUser($this->user)->where('source_lesson_id', $lesson->id)->first();
    $item = PracticeItem::forUser($this->user)->first();
    // Histórico de antes do registro: respostas sem eventos de XP.
    foreach (range(1, 3) as $n) {
        (new CardReview)->forceFill([
            'user_id' => $this->user->id, 'card_id' => $card->id, 'rating' => Rating::Good, 'interval_before' => 0,
            'interval_after' => 1, 'ease_after' => 2.5, 'was_new' => $n === 1, 'reviewed_at' => now()->subDays($n),
        ])->save();
    }
    (new Attempt)->forceFill([
        'user_id' => $this->user->id, 'practice_item_id' => $item->id, 'answer' => 'since', 'correction' => [],
        'verdict' => Verdict::Total, 'source' => 'key', 'xp' => 10, 'answered_at' => now()->subDay(),
    ])->save();
    PracticeSession::forUser($this->user)->update(['status' => SessionStatus::Completed, 'completed_at' => now()->subDay()]);

    $this->artisan('fixa:xp-rebuild')->assertSuccessful();
    $first = $this->ledger->total($this->user);
    $this->artisan('fixa:xp-rebuild')->expectsOutput('Eventos de XP criados: 0.')->assertSuccessful();

    expect($first)->toBe(3 * 2 + 10 + 10)
        ->and($this->ledger->total($this->user))->toBe($first);
});

test('XP events can never be changed or removed', function () {
    $this->ledger->award($this->user, XpSource::CardReview, 1, 2);
    $event = XpEvent::forUser($this->user)->sole();

    expect(fn () => $event->forceFill(['xp' => 0])->save())->toThrow(LogicException::class)
        ->and(fn () => $event->delete())->toThrow(LogicException::class)
        ->and(fn () => $this->ledger->award($this->user, XpSource::Attempt, 9, -5))->not->toThrow(Throwable::class)
        ->and($this->ledger->total($this->user))->toBe(2);
});

test('XP never goes down, whatever the student does (property test, GAM-03)', function () {
    mt_srand(5);
    $lessons = [($this->lesson)(), ($this->lesson)()];
    $total = 0;

    foreach (range(1, 60) as $step) {
        $action = mt_rand(1, 6);
        $alive = Lesson::forUser($this->user)->pluck('id');

        match (true) {
            $action <= 2 && ($card = Card::forUser($this->user)->inRandomOrder()->first()) !== null => $this->postJson(route('cards.reviews.store', $card), ['rating' => mt_rand(1, 4)]),
            $action === 3 && ($item = PracticeItem::forUser($this->user)->whereNotIn('id', Attempt::forUser($this->user)->select('practice_item_id'))->inRandomOrder()->first()) !== null => $this->postJson(route('practice.attempts.store', $item), ['answer' => mt_rand(0, 1) ? 'since' : 'for']),
            $action === 4 && $alive->count() > 1 => app(DeleteLesson::class)->handle($this->user, Lesson::forUser($this->user)->findOrFail($alive->random())),
            $action === 5 => $this->ledger->rebuild($this->user),
            $action === 6 && $alive->count() < 3 => $lessons[] = ($this->lesson)(),
            default => null,
        };

        $now = $this->ledger->total($this->user);
        expect($now)->toBeGreaterThanOrEqual($total);
        $total = $now;
    }

    expect($total)->toBeGreaterThan(0);
});

test('one student XP never counts for another', function () {
    $other = User::factory()->create();
    ($this->lesson)();
    $this->postJson(route('cards.reviews.store', Card::forUser($this->user)->first()), ['rating' => 3])->assertOk();

    $this->ledger->rebuild();

    expect($this->ledger->total($other))->toBe(0);
});
