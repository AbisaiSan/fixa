<?php

use App\Domain\Lessons\InvalidLessonTransition;
use App\Domain\Lessons\LessonStatus;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('a lesson belongs to the student enrollment and starts queued', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->forUser($user)->create();

    expect($lesson->status)->toBe(LessonStatus::Queued)
        ->and($lesson->enrollment_id)->toBe($user->activeEnrollment->id)
        ->and($lesson->language)->toBe('en')
        ->and($lesson->ulid)->toHaveLength(26);
});

test('the happy path goes from queued to ready', function () {
    $lesson = Lesson::factory()->create();

    foreach ([LessonStatus::Extracting, LessonStatus::Generating, LessonStatus::Validating, LessonStatus::Ready] as $status) {
        $lesson->transitionTo($status);
    }

    expect($lesson->fresh()->status)->toBe(LessonStatus::Ready);
});

test('invalid transitions are refused', function (LessonStatus $from, LessonStatus $to) {
    $lesson = Lesson::factory()->status($from)->create();

    expect(fn () => $lesson->transitionTo($to))->toThrow(InvalidLessonTransition::class)
        ->and($lesson->fresh()->status)->toBe($from);
})->with([
    'pular a extração' => [LessonStatus::Queued, LessonStatus::Ready],
    'pronta sem validar' => [LessonStatus::Generating, LessonStatus::Ready],
    'reabrir uma falha direto' => [LessonStatus::Failed, LessonStatus::Ready],
    'voltar a extrair depois de pronta' => [LessonStatus::Ready, LessonStatus::Extracting],
]);

test('a failure keeps only the reason code, and a retry clears it', function () {
    $lesson = Lesson::factory()->status(LessonStatus::Extracting)->create();

    $lesson->transitionTo(LessonStatus::Failed, 'extraction_failed');
    expect($lesson->fresh()->failure_reason)->toBe('extraction_failed');

    $lesson->transitionTo(LessonStatus::Queued);
    expect($lesson->fresh()->failure_reason)->toBeNull();
});

test('a ready lesson can only be generated again', function () {
    expect(LessonStatus::Ready->allowedNext())->toBe([LessonStatus::Generating]);
});

test('the extracted text is encrypted at rest and hidden from serialization', function () {
    $lesson = Lesson::factory()->create();
    $lesson->forceFill(['extracted_text' => 'I have worked here since 2022.'])->save();

    $raw = DB::table('lessons')->where('id', $lesson->id)->value('extracted_text');

    expect($raw)->not->toContain('worked here')
        ->and($lesson->fresh()->extracted_text)->toBe('I have worked here since 2022.')
        ->and($lesson->toArray())->not->toHaveKey('extracted_text');
});

test('lessons are isolated between students', function () {
    $mine = Lesson::factory()->create();
    Lesson::factory()->create();

    $this->actingAs(User::findOrFail($mine->user_id));

    expect(Lesson::pluck('id')->all())->toBe([$mine->id]);
});
