<?php

use App\Domain\Cards\Scheduling\Rating;
use App\Models\Card;
use App\Models\CardReview;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

beforeEach(function () {
    Date::setTestNow(CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->card = Card::factory()->forLesson(Lesson::factory()->forUser($this->user)->create())->create();
    $this->actingAs($this->user);
});

afterEach(fn () => Date::setTestNow());

test('each answer updates the card and is recorded in card_reviews', function () {
    $this->postJson(route('cards.reviews.store', $this->card), ['rating' => Rating::Good->value, 'duration_ms' => 4200])
        ->assertOk()
        ->assertJson(['card' => $this->card->ulid, 'interval_days' => 2, 'due_at' => '2026-10-26T03:00:00+00:00']);

    $card = $this->card->fresh();
    $review = CardReview::sole();

    expect($card->repetitions)->toBe(1)
        ->and($card->first_reviewed_at?->toIso8601String())->toBe('2026-10-24T18:00:00+00:00')
        ->and($review->rating)->toBe(Rating::Good)
        ->and($review->duration_ms)->toBe(4200)
        ->and($review->interval_before)->toBe(0)
        ->and($review->interval_after)->toBe(2)
        ->and($review->was_new)->toBeTrue()
        ->and($review->reviewed_at->toIso8601String())->toBe('2026-10-24T18:00:00+00:00');
});

test('a miss sends the card back to the session', function () {
    $this->card->forceFill(['repetitions' => 3, 'interval_days' => 10, 'due_at' => now()->subHour(), 'first_reviewed_at' => now()->subDays(20)])->save();

    $this->postJson(route('cards.reviews.store', $this->card), ['rating' => Rating::Again->value])->assertOk();

    expect($this->card->fresh()->repetitions)->toBe(0)
        ->and($this->card->fresh()->lapses)->toBe(1)
        ->and(CardReview::sole()->was_new)->toBeFalse()
        ->and(CardReview::sole()->interval_before)->toBe(10);
});

test('the same answer sent twice after going offline counts once', function () {
    $id = (string) Str::uuid();

    $this->postJson(route('cards.reviews.store', $this->card), ['rating' => 3, 'client_review_id' => $id])->assertOk();
    $this->postJson(route('cards.reviews.store', $this->card), ['rating' => 3, 'client_review_id' => $id])->assertOk();

    expect(CardReview::count())->toBe(1)->and($this->card->fresh()->repetitions)->toBe(1);
});

test('an invalid rating is refused', function () {
    $this->postJson(route('cards.reviews.store', $this->card), ['rating' => 5])->assertUnprocessable();

    expect(CardReview::count())->toBe(0);
});
