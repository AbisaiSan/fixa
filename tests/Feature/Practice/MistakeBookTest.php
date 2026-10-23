<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Practice\ErrorCategory;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create();
    $this->makeItems = function () {
        $session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $this->lesson->id, 'kind' => SessionKind::Writing]);
        $session->save();

        return collect(AiFixtures::activities()['items'])->map(function (array $raw, int $index) use ($session) {
            $item = (new PracticeItem)->forceFill([
                'user_id' => $this->user->id, 'practice_session_id' => $session->id, 'position' => $index + 1,
                'type' => ItemType::from($raw['type']), 'context' => $raw['context'],
                'content' => collect($raw)->except(['type', 'context'])->all(),
            ]);
            $item->save();

            return $item;
        });
    };
    $this->items = ($this->makeItems)();
    $this->actingAs($this->user);
    $this->answer = fn (PracticeItem $item, string $answer) => $this->postJson(route('practice.attempts.store', $item), ['answer' => $answer]);
});

test('a wrong answer opens a mistake with the student text, correction, category and lesson (ERR-01)', function () {
    ($this->answer)($this->items[0], 'worked');

    $mistake = Mistake::sole();

    expect($mistake->content['answer'])->toBe('worked')
        ->and($mistake->content['corrected'])->toBe('I have worked at this company since 2021.')
        ->and($mistake->content['point'])->toBe('have + particípio')
        ->and($mistake->content['explanation'])->toContain('since')
        ->and($mistake->category)->toBe(ErrorCategory::Grammar)
        ->and($mistake->source_lesson_id)->toBe($this->lesson->id)
        ->and($mistake->repetitions)->toBe(1);
});

test('a correct answer opens no mistake', function () {
    ($this->answer)($this->items[0], 'have worked');

    expect(Mistake::count())->toBe(0);
});

test('the same point again counts a repetition instead of a new mistake', function () {
    ($this->answer)($this->items[0], 'worked');
    ($this->answer)(($this->makeItems)()[0], 'work');

    expect(Mistake::sole()->repetitions)->toBe(2)
        ->and(Mistake::sole()->content['answer'])->toBe('work');
});

test('an AI correction files the mistake under its category and point', function () {
    app(FakeAiProvider::class)->respondWith(AiTask::TextCorrection, [
        'verdict' => 'wrong', 'error' => 'in sale', 'category' => 'preposition',
        'explanation' => 'Áreas de atuação levam in.', 'corrected' => 'I have never worked in sales.',
        'natural' => "I've never worked in sales.", 'point' => 'work in',
    ]);

    ($this->answer)($this->items[1], 'I never worked on sale.');

    expect(Mistake::sole()->category)->toBe(ErrorCategory::Preposition)
        ->and(Mistake::sole()->content['point'])->toBe('work in');
});

test('the mistake keeps the student text encrypted and the key has no student text', function () {
    ($this->answer)($this->items[0], 'worked');

    $raw = (array) DB::table('mistakes')->first();

    expect($raw['content'])->not->toContain('worked')
        ->and($raw['point_key'])->toMatch('/^[a-f0-9]{64}$/');
});
