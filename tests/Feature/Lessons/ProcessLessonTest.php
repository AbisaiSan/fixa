<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\LessonStatus;
use App\Jobs\ProcessLesson;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->ai = app(FakeAiProvider::class);
    $this->lesson = Lesson::factory()->create();
    $this->lesson->forceFill(['extracted_text' => str_repeat('I have worked here since 2022. ', 12)])->save();
});

function runJob(Lesson $lesson): void
{
    app()->call([new ProcessLesson($lesson->id), 'handle']);
}

test('a lesson goes from queued to ready with its review stored', function () {
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());

    runJob($this->lesson);

    $lesson = $this->lesson->fresh();
    $review = LessonReview::withoutGlobalScopes()->where('lesson_id', $lesson->id)->sole();

    expect($lesson->status)->toBe(LessonStatus::Ready)
        ->and($lesson->word_count)->toBe(72)
        ->and($review->summary)->toBe(AiFixtures::lessonReview()['summary'])
        ->and($review->concepts)->toBe(AiFixtures::lessonReview()['concepts'])
        ->and($review->provider)->toBe('fake')
        ->and($review->prompt_version)->toBe('v1');
});

test('the stored review is encrypted at rest', function () {
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());

    runJob($this->lesson);

    $raw = (array) DB::table('lesson_reviews')->first();

    expect($raw['summary'])->not->toContain('Present Perfect')
        ->and($raw['concepts'])->not->toContain('worked here');
});

test('the AI never receives personal data of the student', function () {
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    $user = User::findOrFail($this->lesson->user_id);

    runJob($this->lesson);

    $request = $this->ai->sent(AiTask::LessonReview)[0];

    expect($request->instructions.$request->material)->not->toContain($user->email)
        ->and($request->instructions.$request->material)->not->toContain($user->name);
});

test('a short material fails with a reason and no AI call', function () {
    $this->lesson->forceFill(['extracted_text' => 'muito curto'])->save();

    runJob($this->lesson);

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Failed)
        ->and($this->lesson->fresh()->failure_reason)->toBe('too_short');
    $this->ai->assertSentCount(0);
});

test('invalid AI output fails the lesson after one retry', function () {
    $this->ai->respondWith(AiTask::LessonReview, ['summary' => 'x'], ['summary' => 'x']);

    runJob($this->lesson);

    expect($this->lesson->fresh()->failure_reason)->toBe('ai_invalid_output');
});

test('an overloaded provider is retried by the queue, keeping the progress', function () {
    $this->ai->respondWith(AiTask::LessonReview, new AiProviderException('busy', retryable: true), AiFixtures::lessonReview());

    expect(fn () => runJob($this->lesson))->toThrow(AiProviderException::class);
    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Generating);

    runJob($this->lesson);

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Ready);
});

test('when retries run out the lesson shows as failed', function () {
    $this->lesson->forceFill(['status' => LessonStatus::Generating])->save();

    (new ProcessLesson($this->lesson->id))->failed(new AiProviderException('busy', retryable: true));

    expect($this->lesson->fresh()->failure_reason)->toBe('ai_unavailable');
});

test('sending a lesson queues its processing', function () {
    Queue::fake();
    $user = User::findOrFail($this->lesson->user_id);

    $this->actingAs($user)->post(route('lessons.store'), [
        'source' => 'text', 'title' => 'Aula', 'lesson_date' => '2026-10-21', 'rights' => '1',
        'text' => str_repeat('palavra ', 60),
    ]);

    Queue::assertPushed(ProcessLesson::class);
});

test('the lesson page carries the review when ready', function () {
    $this->ai->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    runJob($this->lesson);

    $this->actingAs(User::findOrFail($this->lesson->user_id))
        ->get(route('lessons.show', $this->lesson))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lesson.status', 'ready')
            ->where('review.summary', AiFixtures::lessonReview()['summary'])
            ->has('review.concepts', 1)
            ->missing('lesson.extracted_text'));
});
