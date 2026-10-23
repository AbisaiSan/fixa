<?php

use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create(['lesson_date' => '2026-10-21']);
    $this->actingAs($this->user);
});

test('the session brings due reviews before new cards with the interval of each button', function () {
    $word = Word::factory()->forLesson($this->lesson)->create();
    $new = Card::factory()->forLesson($this->lesson)->create(['word_id' => $word->id]);
    $due = Card::factory()->forLesson($this->lesson)->create([
        'front' => 'strength', 'back' => 'ponto forte',
        'repetitions' => 3, 'interval_days' => 10, 'due_at' => now()->subHour(), 'first_reviewed_at' => now()->subDays(20),
    ]);

    $this->get(route('flashcards'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('practice/Flashcards')
            ->where('mode', 'normal')
            ->where('lesson', null)
            ->where('cards.0.ulid', $due->ulid)
            ->where('cards.0.is_new', false)
            ->where('cards.0.preview', [0, 12, 25, 35])
            ->where('cards.1.ulid', $new->ulid)
            ->where('cards.1.is_new', true)
            ->where('cards.1.part_of_speech', 'substantivo')
            ->where('cards.1.lesson_date', '2026-10-21')
            ->where('cards.1.preview', [0, 1, 2, 4])
            ->missing('cards.0.id'));
});

test('"Praticar esta aula" brings only that lesson cards', function () {
    $other = Lesson::factory()->forUser($this->user)->create();
    Card::factory()->forLesson($this->lesson)->create();
    Card::factory()->forLesson($other)->create(['front' => 'outra']);

    $this->get(route('flashcards', ['aula' => $this->lesson->ulid]))
        ->assertInertia(fn (Assert $page) => $page->has('cards', 1)->where('lesson.ulid', $this->lesson->ulid));
});

test('a lesson of another student is not found', function () {
    $this->get(route('flashcards', ['aula' => Lesson::factory()->create()->ulid]))->assertNotFound();
});

test('the answer returns the new preview for a card that comes back', function () {
    $card = Card::factory()->forLesson($this->lesson)->create();

    $this->postJson(route('cards.reviews.store', $card), ['rating' => 1])
        ->assertJson(['interval_days' => 0, 'preview' => [0, 1, 2, 4]]);
});
