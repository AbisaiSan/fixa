<?php

use App\Domain\Cards\CardKind;
use App\Domain\Cards\DailyQueue;
use App\Domain\Lessons\Reports\ReportStatus;
use App\Models\Card;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create();
    $this->card = Card::factory()->forLesson($this->lesson)->create();
    $this->actingAs($this->user);
});

test('the student creates a card of their own, with or without a lesson', function () {
    $this->post(route('cards.store'), ['front' => 'to meet a deadline', 'back' => 'cumprir um prazo'])
        ->assertSessionHas('status', 'card-created');
    $this->post(route('cards.store'), [
        'front' => 'Could you walk me through it?', 'back' => 'Pode me explicar passo a passo?', 'lesson' => $this->lesson->ulid,
    ]);

    $own = Card::where('front', 'to meet a deadline')->sole();
    $fromLesson = Card::where('front', 'Could you walk me through it?')->sole();

    expect($own->source_lesson_id)->toBeNull()
        ->and($own->provider)->toBeNull()
        ->and($own->prompt_version)->toBeNull()
        ->and($own->kind)->toBe(CardKind::Word)
        ->and($fromLesson->source_lesson_id)->toBe($this->lesson->id)
        ->and($fromLesson->kind)->toBe(CardKind::Phrase);
});

test('a card for a lesson of another student is not found', function () {
    $other = Lesson::factory()->create();

    $this->post(route('cards.store'), ['front' => 'x', 'back' => 'y', 'lesson' => $other->ulid])->assertNotFound();

    expect(Card::count())->toBe(1);
});

test('the same front and meaning cannot be created twice', function () {
    $this->post(route('cards.store'), ['front' => 'Deadline', 'back' => 'prazo final'])
        ->assertSessionHasErrors(['front' => 'Você já tem um cartão com esta frente e este significado.']);
});

test('the student edits a card', function () {
    $this->patch(route('cards.update', $this->card), ['front' => 'deadline', 'back' => 'prazo', 'example' => '', 'example_translation' => ''])
        ->assertSessionHas('status', 'card-updated');

    $card = $this->card->fresh();

    expect($card->back)->toBe('prazo')
        ->and($card->example)->toBeNull()
        ->and($card->dedupe_key)->not->toBe($this->card->dedupe_key);
});

test('editing into a card that already exists is refused', function () {
    Card::factory()->forLesson($this->lesson)->create(['front' => 'strength', 'back' => 'ponto forte']);

    $this->patch(route('cards.update', $this->card), ['front' => 'strength', 'back' => 'ponto forte'])
        ->assertSessionHasErrors('front');
});

test('a suspended card leaves the sessions until reactivated', function () {
    $queue = app(DailyQueue::class);

    $this->post(route('cards.suspend', $this->card))->assertSessionHas('status', 'card-suspended');
    expect($queue->for($this->user))->toHaveCount(0);

    $this->delete(route('cards.resume', $this->card))->assertSessionHas('status', 'card-resumed');
    expect($queue->for($this->user))->toHaveCount(1);
});

test('reporting a card takes it out of the routine and undo brings it back', function () {
    $this->post(route('cards.reports.store', $this->card), ['reason' => 'wrong_translation', 'details' => 'o certo é prazo'])
        ->assertSessionHas('status', 'item-reported');

    $report = ItemReport::sole();

    expect($this->card->fresh()->reported)->toBeTrue()
        ->and($report->reportable_type)->toBe('card')
        ->and($report->source_lesson_id)->toBe($this->lesson->id)
        ->and(app(DailyQueue::class)->for($this->user))->toHaveCount(0);

    $this->delete(route('cards.reports.destroy', [$this->card, $report]))->assertSessionHas('status', 'report-withdrawn');

    expect($this->card->fresh()->reported)->toBeFalse()
        ->and($report->fresh()->status)->toBe(ReportStatus::Withdrawn);
});

test('a report of another card cannot be undone through this one', function () {
    $other = Card::factory()->forLesson($this->lesson)->create(['front' => 'outro']);
    $this->post(route('cards.reports.store', $other), ['reason' => 'other']);

    $this->delete(route('cards.reports.destroy', [$this->card, ItemReport::sole()]))->assertNotFound();
});

test('removing a card removes its reports', function () {
    $this->post(route('cards.reports.store', $this->card), ['reason' => 'other']);

    $this->delete(route('cards.destroy', $this->card))->assertSessionHas('status', 'card-deleted');

    expect(Card::count())->toBe(0)->and(ItemReport::count())->toBe(0);
});
