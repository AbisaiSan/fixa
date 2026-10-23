<?php

use App\Domain\Cards\DailyQueue;
use App\Domain\Vocabulary\WordState;
use App\Models\Card;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create(['lesson_date' => '2026-10-21']);
    $this->word = Word::factory()->forLesson($this->lesson)->create(['state' => WordState::Learning, 'provider' => 'fake']);
    Word::factory()->forLesson($this->lesson)->create(['term' => 'strength', 'translation' => 'ponto forte', 'state' => WordState::Mastered]);
    $this->actingAs($this->user);
});

test('the list shows the words with counts per state for the chips', function () {
    $this->get(route('vocabulary.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Vocabulary')
            ->where('status', 'ready')
            ->has('words', 2)
            ->where('words.0.term', 'deadline')
            ->where('counts', ['all' => 2, 'new' => 0, 'learning' => 1, 'mastered' => 1])
            ->where('lessons.0.ulid', $this->lesson->ulid)
            ->where('selected', null)
            ->where('aiLeft', 30)
            ->missing('words.0.id'));
});

test('search and filters come from the URL', function () {
    $this->get(route('vocabulary.index', ['q' => 'FORTE', 'estado' => 'mastered']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('words', 1)
            ->where('words.0.term', 'strength')
            ->where('filters.q', 'FORTE')
            ->where('counts.all', 1));
});

test('the detail carries every VOC-01 field and the origin', function () {
    $this->get(route('words.show', $this->word))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.ulid', $this->word->ulid)
            ->where('selected.definition', 'A data ou hora em que algo precisa estar pronto.')
            ->has('selected.examples', 2)
            ->has('selected.collocations', 3)
            ->where('selected.lesson.lesson_date', '2026-10-21')
            ->where('selected.generated', true)
            ->where('selected.report', null));
});

test('editing a word also updates the back of its card', function () {
    $card = Card::factory()->forLesson($this->lesson)->create(['word_id' => $this->word->id]);

    $this->patch(route('words.update', $this->word), ['translation' => 'prazo', 'definition' => ''])
        ->assertSessionHas('status', 'word-updated');

    expect($this->word->fresh()->translation)->toBe('prazo')
        ->and($this->word->fresh()->definition)->toBeNull()
        ->and($card->fresh()->back)->toBe('prazo');
});

test('a reported word leaves the flashcards until undone (MAT-05)', function () {
    Card::factory()->forLesson($this->lesson)->create(['word_id' => $this->word->id]);
    $queue = app(DailyQueue::class);

    $this->post(route('words.reports.store', $this->word), ['reason' => 'wrong_translation'])
        ->assertSessionHas('status', 'item-reported');

    expect($this->word->fresh()->reported)->toBeTrue()
        ->and($queue->for($this->user))->toHaveCount(0);

    $this->get(route('words.show', $this->word))
        ->assertInertia(fn (Assert $page) => $page->where('selected.report', ItemReport::sole()->ulid));

    $this->delete(route('words.reports.destroy', [$this->word, ItemReport::sole()]));

    expect($queue->for($this->user))->toHaveCount(1);
});

test('a new student sees the empty vocabulary', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('vocabulary.index'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'empty')->has('words', 0));
});
