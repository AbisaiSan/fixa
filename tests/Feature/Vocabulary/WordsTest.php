<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Cards\ReviewCard;
use App\Domain\Cards\Scheduling\Rating;
use App\Domain\Lessons\DeleteLesson;
use App\Domain\Vocabulary\WordOrigin;
use App\Domain\Vocabulary\WordState;
use App\Jobs\ProcessLesson;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->ai = app(FakeAiProvider::class);
    $this->lesson = Lesson::factory()->create();
    $this->lesson->forceFill(['extracted_text' => str_repeat('I have worked here since 2022. ', 12)])->save();
    $this->run = function (Lesson $lesson) {
        $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview())->respondWith(AiTask::Cards, AiFixtures::cards());
        app()->call([new ProcessLesson($lesson->id), 'handle']);
    };
});

test('word cards also enter the vocabulary with every VOC-01 field', function () {
    ($this->run)($this->lesson);

    $word = Word::forUser($this->lesson->user)->sole();
    $card = Card::forUser($this->lesson->user)->where('front', 'deadline')->sole();

    expect($word->term)->toBe('deadline')
        ->and($word->translation)->toBe('prazo final')
        ->and($word->part_of_speech)->toBe('substantivo')
        ->and($word->definition)->toBe('A data ou hora em que algo precisa estar pronto.')
        ->and($word->examples)->toHaveCount(2)
        ->and($word->collocations)->toBe(['meet a deadline', 'miss a deadline'])
        ->and($word->origin)->toBe(WordOrigin::Lesson)
        ->and($word->source_lesson_id)->toBe($this->lesson->id)
        ->and($word->language)->toBe('en')
        ->and($word->state)->toBe(WordState::New)
        ->and($word->prompt_version)->toBe('v2')
        ->and($card->word_id)->toBe($word->id);
});

test('phrases do not become words', function () {
    ($this->run)($this->lesson);

    expect(Word::withoutGlobalScopes()->where('term', 'like', 'Have you%')->exists())->toBeFalse();
});

test('the same word in a second lesson only gains the reference', function () {
    ($this->run)($this->lesson);
    $second = Lesson::factory()->forUser($this->lesson->user)->create();
    $second->forceFill(['extracted_text' => $this->lesson->extracted_text])->save();

    ($this->run)($second);

    $word = Word::forUser($this->lesson->user)->sole();

    expect(DB::table('lesson_word')->where('word_id', $word->id)->count())->toBe(2)
        ->and($word->source_lesson_id)->toBe($this->lesson->id);
});

test('the word state follows the answers to its card (VOC-02)', function () {
    ($this->run)($this->lesson);
    $user = $this->lesson->user;
    $card = Card::forUser($user)->where('front', 'deadline')->sole();
    $review = app(ReviewCard::class);

    $review->handle($user, $card, Rating::Good);
    expect(Word::forUser($user)->sole()->state)->toBe(WordState::Learning);

    $card->refresh()->forceFill(['interval_days' => 25, 'repetitions' => 4])->save();
    $review->handle($user, $card, Rating::Good);
    $review->handle($user, $card->refresh(), Rating::Good);

    expect(Word::forUser($user)->sole()->state)->toBe(WordState::Mastered);

    $review->handle($user, $card->refresh(), Rating::Again);
    expect(Word::forUser($user)->sole()->state)->toBe(WordState::Learning);
});

test('word details out of the rules are refused by the schema', function () {
    $bad = AiFixtures::cards();
    $bad['cards'][0]['word']['examples'] = [$bad['cards'][0]['word']['examples'][0]];
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview())->respondWith(AiTask::Cards, $bad, $bad);

    app()->call([new ProcessLesson($this->lesson->id), 'handle']);

    expect($this->lesson->fresh()->failure_reason)->toBe('ai_invalid_output')
        ->and(Word::withoutGlobalScopes()->count())->toBe(0);
});

test('removing a lesson removes its own words and keeps shared and added ones', function () {
    $user = $this->lesson->user;
    $other = Lesson::factory()->forUser($user)->create();
    $own = Word::factory()->forLesson($this->lesson)->create(['term' => 'deadline']);
    $shared = Word::factory()->forLesson($this->lesson)->create(['term' => 'strength', 'translation' => 'ponto forte']);
    $mine = Word::factory()->create(['user_id' => $user->id, 'origin' => WordOrigin::Mine, 'term' => 'meu']);
    DB::table('lesson_word')->insert([
        ['word_id' => $own->id, 'lesson_id' => $this->lesson->id],
        ['word_id' => $shared->id, 'lesson_id' => $this->lesson->id],
        ['word_id' => $shared->id, 'lesson_id' => $other->id],
    ]);

    $this->actingAs($user);
    app(DeleteLesson::class)->handle($user, $this->lesson);

    expect(Word::withoutGlobalScopes()->orderBy('term')->pluck('term')->all())->toBe(['meu', 'strength'])
        ->and($shared->fresh()->source_lesson_id)->toBe($other->id)
        ->and($mine->fresh())->not->toBeNull();
});
