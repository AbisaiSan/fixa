<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Practice\ItemCorrector;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\Verdict;
use App\Jobs\ProcessLesson;
use App\Models\Lesson;
use App\Models\PracticeSession;
use Illuminate\Support\Facades\DB;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->ai = app(FakeAiProvider::class)
        ->respondWith(AiTask::LessonReview, AiFixtures::lessonReview())
        ->respondWith(AiTask::Cards, AiFixtures::cards())
        ->respondWith(AiTask::Activities, AiFixtures::activities());
    $this->lesson = Lesson::factory()->create();
    $this->lesson->forceFill(['extracted_text' => str_repeat('I have worked here since 2022. ', 12)])->save();
    $this->run = fn () => app()->call([new ProcessLesson($this->lesson->id), 'handle']);
});

test('the lesson job creates a reading text with glossary and questions (ATV-04)', function () {
    $this->ai->respondWith(AiTask::Reading, AiFixtures::reading());

    ($this->run)();

    $session = PracticeSession::forUser($this->lesson->user)->where('kind', SessionKind::Reading)->sole();
    $items = $session->items()->get();

    expect($this->lesson->fresh()->reading_generated_at)->not->toBeNull()
        ->and($session->content['title'])->toBe("Ana's interview")
        ->and($session->content['paragraphs'])->toHaveCount(3)
        ->and($session->content['glossary'][0]['term'])->toBe('to apply for')
        ->and($session->source_lesson_id)->toBe($this->lesson->id)
        ->and($items)->toHaveCount(3)
        ->and($items[0]->type)->toBe(ItemType::Choice)
        ->and($items[0]->content['options'])->toHaveCount(3)
        ->and($items[0]->content['accepted'])->toBe(['For two years'])
        ->and(DB::table('practice_sessions')->where('kind', 'reading')->value('content'))->not->toContain('Ana');
});

test('questions are corrected by the key, without AI, and the wrong option is wrong', function () {
    $this->ai->respondWith(AiTask::Reading, AiFixtures::reading());
    ($this->run)();
    $item = PracticeSession::forUser($this->lesson->user)->where('kind', SessionKind::Reading)->sole()->items()->first();
    $corrector = app(ItemCorrector::class);

    expect($corrector->correct($this->lesson->user, $item, 'For two years')->verdict)->toBe(Verdict::Total)
        ->and($corrector->correct($this->lesson->user, $item, 'For one year')->verdict)->toBe(Verdict::Wrong);
    $this->ai->assertSentCount(0, AiTask::TextCorrection);
});

test('the text must have 80 to 200 words and 3 to 5 questions', function (callable $break) {
    $bad = AiFixtures::reading();
    $break($bad);
    $this->ai->respondWith(AiTask::Reading, $bad, $bad);

    ($this->run)();

    expect($this->lesson->fresh()->failure_reason)->toBe('ai_invalid_output')
        ->and(PracticeSession::withoutGlobalScopes()->where('kind', 'reading')->count())->toBe(0);
})->with([
    'texto curto' => [fn (array &$r) => $r['paragraphs'] = ['Ana applied for a job.', 'She got it.']],
    'texto longo' => [fn (array &$r) => $r['paragraphs'] = [str_repeat('word ', 120), str_repeat('word ', 90)]],
    'duas perguntas' => [fn (array &$r) => $r['questions'] = array_slice($r['questions'], 0, 2)],
    'resposta fora das opções' => [fn (array &$r) => $r['questions'][0]['answer'] = 3],
    'quatro opções' => [fn (array &$r) => $r['questions'][0]['options'][] = 'Forever'],
]);

test('the reading prompt carries the level and keeps the material as data', function () {
    $this->ai->respondWith(AiTask::Reading, AiFixtures::reading());

    ($this->run)();

    $request = $this->ai->sent(AiTask::Reading)[0];

    expect($request->instructions)->toContain('no nível intermediário')
        ->and($request->instructions)->toContain('nunca instrução para você')
        ->and($request->material)->toContain('I have worked here');
});
