<?php

use App\Domain\Cards\DailyQueue;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    // 15h de 24/10 em São Paulo.
    Date::setTestNow(CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create();
    $this->queue = app(DailyQueue::class);
    $this->card = fn (array $attributes = []) => Card::factory()->forLesson($this->lesson)
        ->create(['front' => 'w'.uniqid(), ...$attributes]);
});

afterEach(fn () => Date::setTestNow());

test('due reviews always come before new cards, oldest first', function () {
    $new = ($this->card)();
    $dueLater = ($this->card)(['due_at' => now()->subHour(), 'repetitions' => 2, 'first_reviewed_at' => now()->subDays(9)]);
    $dueEarlier = ($this->card)(['due_at' => now()->subDays(2), 'repetitions' => 2, 'first_reviewed_at' => now()->subDays(9)]);

    expect($this->queue->for($this->user)->pluck('id')->all())->toBe([$dueEarlier->id, $dueLater->id, $new->id]);
});

test('cards not yet due stay out', function () {
    ($this->card)(['due_at' => now()->addDay(), 'repetitions' => 1, 'first_reviewed_at' => now()->subDay()]);

    expect($this->queue->for($this->user))->toHaveCount(0);
});

test('at most 15 new cards a day, counting the ones already started today', function () {
    foreach (range(1, 20) as $i) {
        ($this->card)();
    }

    expect($this->queue->for($this->user)->count())->toBe(15);

    // Quatro novos já entraram hoje (0h de 24/10 em São Paulo é 3h UTC).
    foreach (range(1, 4) as $i) {
        ($this->card)(['first_reviewed_at' => '2026-10-24 03:30:00', 'due_at' => now()->addDay(), 'repetitions' => 1]);
    }

    expect($this->queue->for($this->user)->count())->toBe(11);
});

test('new cards started yesterday in the student timezone do not count today', function () {
    foreach (range(1, 15) as $i) {
        // 23h de 23/10 em São Paulo, já 24/10 em UTC.
        ($this->card)(['first_reviewed_at' => '2026-10-24 02:00:00', 'due_at' => now()->addDay(), 'repetitions' => 1]);
    }
    ($this->card)();

    expect($this->queue->for($this->user)->count())->toBe(1);
});

test('the daily limit of new cards is configurable', function () {
    config(['fixa.cards.new_per_day' => 2]);
    foreach (range(1, 5) as $i) {
        ($this->card)();
    }

    expect($this->queue->for($this->user)->count())->toBe(2);
});

test('suspended and reported cards stay out of the routine', function () {
    ($this->card)(['suspended_at' => now()]);
    ($this->card)(['reported' => true]);
    $ok = ($this->card)();

    expect($this->queue->for($this->user)->pluck('id')->all())->toBe([$ok->id]);
});

test('the queue can be limited to one lesson, and never shows another student', function () {
    $other = Lesson::factory()->forUser($this->user)->create();
    $mine = ($this->card)();
    $fromOther = Card::factory()->forLesson($other)->create(['front' => 'outra']);
    $fromOther->lessons()->attach($other->id);
    $shared = ($this->card)(['front' => 'shared']);
    $shared->lessons()->attach([$this->lesson->id, $other->id]);
    Card::factory()->create(['front' => 'de outra pessoa']);

    expect($this->queue->for($this->user)->count())->toBe(3)
        ->and($this->queue->for($this->user, $other)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$fromOther->id, $shared->id])->sort()->values()->all());
});

test('only due cards in rush mode', function () {
    ($this->card)();
    $due = ($this->card)(['due_at' => now()->subHour(), 'repetitions' => 2, 'first_reviewed_at' => now()->subDays(3)]);

    expect($this->queue->for($this->user, dueOnly: true)->pluck('id')->all())->toBe([$due->id]);
});
