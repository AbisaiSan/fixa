<?php

use App\Domain\Practice\SessionKind;
use App\Domain\Vocabulary\WordOrigin;
use App\Models\Card;
use App\Models\PracticeSession;
use App\Models\User;
use App\Models\Word;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $reading = AiFixtures::reading();
    $this->session = (new PracticeSession)->forceFill([
        'user_id' => $this->user->id, 'kind' => SessionKind::Reading,
        'content' => ['title' => $reading['title'], 'paragraphs' => $reading['paragraphs'], 'glossary' => $reading['glossary']],
    ]);
    $this->session->save();
    $this->actingAs($this->user);
});

test('a word from the text is saved with one tap, from the glossary and without AI (ATV-04, VOC-03)', function () {
    $this->postJson(route('practice.reading.words.store', $this->session), ['form' => 'Applied'])
        ->assertOk()
        ->assertJson(['term' => 'to apply for']);

    $word = Word::sole();

    expect($word->translation)->toBe('candidatar-se a')
        ->and($word->part_of_speech)->toBe('verbo')
        ->and($word->origin)->toBe(WordOrigin::Mine)
        ->and($word->provider)->toBeNull()
        ->and(Card::sole()->word_id)->toBe($word->id);
});

test('tapping the same word again does not duplicate it', function () {
    $this->postJson(route('practice.reading.words.store', $this->session), ['form' => 'applied']);
    $this->postJson(route('practice.reading.words.store', $this->session), ['form' => 'applied'])->assertOk();

    expect(Word::count())->toBe(1)->and(Card::count())->toBe(1);
});

test('only glossary words of a reading session can be saved', function () {
    $this->postJson(route('practice.reading.words.store', $this->session), ['form' => 'banana'])
        ->assertUnprocessable()
        ->assertJson(['error' => 'not_in_glossary']);

    $writing = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'kind' => SessionKind::Writing]);
    $writing->save();

    $this->postJson(route('practice.reading.words.store', $writing), ['form' => 'applied'])->assertNotFound();
    expect(Word::count())->toBe(0);
});
