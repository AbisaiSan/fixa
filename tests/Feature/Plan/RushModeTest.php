<?php

use App\Domain\Lessons\LessonStatus;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // Segunda, 26/10, 9h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create();
    $this->cards = Card::factory()->count(6)->forLesson($lesson)
        ->sequence(fn ($sequence) => ['front' => "word {$sequence->index}"])
        ->create(['due_at' => now()->subDay(), 'first_reviewed_at' => now()->subDays(3)]);
    $this->today = fn (): PlanDay => PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->sole();
    $this->actingAs($this->user);
});

test('opening the rush starts its clock on the server, once a day', function () {
    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();
    $started = ($this->today)()->rush_started_at;
    $this->travel(2)->minutes();
    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();

    expect($started)->not->toBeNull()
        ->and(($this->today)()->rush_started_at?->equalTo($started))->toBeTrue();
});

test('a 5 minute rush with answers counts the day (PLA-04, GAM-01)', function () {
    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();
    foreach ($this->cards->take(3) as $card) {
        $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
    }
    $this->travel(5)->minutes();

    $this->postJson(route('rush.store'))->assertOk()->assertJson(['counted' => true]);

    expect(($this->today)()->counted_at)->not->toBeNull()
        ->and(($this->today)()->rush_completed_at)->not->toBeNull();
});

test('stopping the rush early with due cards left does not count', function () {
    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();
    $this->postJson(route('cards.reviews.store', $this->cards[0]), ['rating' => 3])->assertOk();
    $this->travel(1)->minute();

    $this->postJson(route('rush.store'))->assertOk()->assertJson(['counted' => false]);

    expect(($this->today)()->counted_at)->toBeNull();
});

test('finishing without opening the rush or answering does not count', function () {
    $this->postJson(route('rush.store'))->assertOk()->assertJson(['counted' => false]);

    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();
    $this->travel(6)->minutes();

    $this->postJson(route('rush.store'))->assertOk()->assertJson(['counted' => false]);
    expect(($this->today)()->counted_at)->toBeNull();
});

test('the rush of one student never counts the day of another', function () {
    $other = User::factory()->create();
    $this->get(route('flashcards', ['modo' => 'corrida']))->assertOk();
    foreach ($this->cards->take(3) as $card) {
        $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
    }
    $this->travel(5)->minutes();

    $this->actingAs($other)->postJson(route('rush.store'))->assertOk()->assertJson(['counted' => false]);

    expect(PlanDay::forUser($other)->whereNotNull('counted_at')->count())->toBe(0)
        ->and(($this->today)()->counted_at)->toBeNull();
});

test('guests cannot finish a rush', function () {
    auth()->logout();

    $this->postJson(route('rush.store'))->assertUnauthorized();
});
