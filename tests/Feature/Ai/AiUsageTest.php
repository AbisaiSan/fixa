<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\LessonStatus;
use App\Jobs\ProcessLesson;
use App\Models\AuditLogEntry;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Tests\Support\AiFixtures;

beforeEach(function () {
    config(['fixa.ai.prices.fake-model' => ['input' => 1_000_000, 'output' => 2_000_000], 'fixa.ai.monthly_budget_usd' => 100_000]);
    $this->ai = app(FakeAiProvider::class)->respondWith(AiTask::Cards, AiFixtures::cards());
    $this->lesson = Lesson::factory()->create();
    $this->lesson->forceFill(['extracted_text' => str_repeat('I have worked here since 2022. ', 12)])->save();
});

function process(Lesson $lesson): void
{
    app()->call([new ProcessLesson($lesson->id), 'handle']);
}

test('each AI attempt is recorded with tokens and cost, never content', function () {
    $this->ai->respondWith(AiTask::LessonReview, ['summary' => 'x'], AiFixtures::lessonReview());

    process($this->lesson);

    $calls = DB::table('ai_calls')->where('task', 'lesson_review')->orderBy('id')->get();

    expect($calls->pluck('outcome')->all())->toBe(['invalid', 'ok'])
        ->and($calls[1]->user_id)->toBe($this->lesson->user_id)
        ->and($calls[1]->lesson_id)->toBe($this->lesson->id)
        ->and($calls[1]->provider)->toBe('fake')
        ->and($calls[1]->task)->toBe('lesson_review')
        ->and($calls[1]->prompt_version)->toBe('v1')
        ->and((float) $calls[1]->cost_usd)->toBe((float) ($calls[1]->input_tokens + 2 * $calls[1]->output_tokens))
        ->and(json_encode($calls))->not->toContain('worked here');
});

test('the monthly budget stops new calls and fails the lesson', function () {
    config(['fixa.ai.monthly_budget_usd' => 1]);
    DB::table('ai_calls')->insert(['provider' => 'fake', 'model' => 'fake-model', 'task' => 'lesson_review', 'prompt_version' => 'v1', 'outcome' => 'ok', 'cost_usd' => 1.5, 'created_at' => now()]);

    process($this->lesson);

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Failed)
        ->and($this->lesson->fresh()->failure_reason)->toBe('ai_budget_reached');
    $this->ai->assertSentCount(0);
});

test('last month spending does not count', function () {
    config(['fixa.ai.monthly_budget_usd' => 1]);
    DB::table('ai_calls')->insert(['provider' => 'fake', 'model' => 'fake-model', 'task' => 'lesson_review', 'prompt_version' => 'v1', 'outcome' => 'ok', 'cost_usd' => 5, 'created_at' => now()->subMonth()->startOfMonth()]);
    config(['fixa.ai.prices.fake-model' => ['input' => 0, 'output' => 0]]);
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());

    process($this->lesson);

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Ready);
});

test('crossing 80 percent of the budget raises one audit alert per month', function () {
    config(['fixa.ai.monthly_budget_usd' => 10, 'fixa.ai.prices.fake-model' => ['input' => 0, 'output' => 0]]);
    DB::table('ai_calls')->insert(['provider' => 'fake', 'model' => 'fake-model', 'task' => 'lesson_review', 'prompt_version' => 'v1', 'outcome' => 'ok', 'cost_usd' => 8.5, 'created_at' => now()]);
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview(), AiFixtures::lessonReview());

    $second = Lesson::factory()->create();
    $second->forceFill(['extracted_text' => str_repeat('palavra ', 60)])->save();

    process($this->lesson);
    process($second);

    expect(AuditLogEntry::where('action', 'ai.budget_alert')->count())->toBe(1);
});
