<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\LessonStatus;
use App\Domain\Lessons\Reports\ReportStatus;
use App\Jobs\ProcessLesson;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create();
    // A aula já tem cartões: regenerar a revisão não os recria (J3-B1).
    $this->lesson->forceFill([
        'extracted_text' => str_repeat('I have worked here since 2022. ', 12),
        'cards_generated_at' => now(),
        'activities_generated_at' => now(),
        'reading_generated_at' => now(),
    ])->save();
    $this->review = LessonReview::factory()->for($this->lesson)->create();
    $this->concept = $this->review->concepts[0]['key'];
    $this->actingAs($this->user);
});

test('every generated concept gets a stable key and the review keeps its source lesson', function () {
    app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    $this->lesson->forceFill(['status' => LessonStatus::Generating])->save();

    app()->call([new ProcessLesson($this->lesson->id), 'handle']);

    $review = $this->review->fresh();

    expect($review->lesson_id)->toBe($this->lesson->id)
        ->and($review->reported)->toBeFalse()
        ->and($review->concepts[0]['key'])->toMatch('/^[a-z0-9]{10}$/');
});

test('reporting an item puts it under review and takes it out of the routine', function () {
    $this->post(route('lessons.reports.store', $this->lesson), [
        'item' => LessonReview::conceptItem($this->concept),
        'reason' => 'wrong_translation',
        'details' => 'a tradução certa seria outra',
    ])->assertRedirect()->assertSessionHas('status', 'item-reported');

    $report = ItemReport::sole();

    expect($report->status)->toBe(ReportStatus::Open)
        ->and($report->source_lesson_id)->toBe($this->lesson->id)
        ->and($report->reportable_type)->toBe('lesson_review')
        ->and($report->details)->toBe('a tradução certa seria outra')
        ->and($this->review->fresh()->reported)->toBeTrue();

    $this->get(route('lessons.show', $this->lesson))
        ->assertInertia(fn (Assert $page) => $page
            ->where('review.reported', true)
            ->where('review.open_reports.0.item', LessonReview::conceptItem($this->concept))
            ->where('review.open_reports.0.ulid', $report->ulid));
});

test('the report details are encrypted at rest', function () {
    $this->post(route('lessons.reports.store', $this->lesson), [
        'item' => 'summary', 'reason' => 'other', 'details' => 'texto do aluno',
    ]);

    expect(DB::table('item_reports')->value('details'))->not->toContain('texto do aluno');
});

test('reporting the same item twice keeps a single open report', function () {
    $payload = ['item' => 'common_mistakes', 'reason' => 'other'];

    $this->post(route('lessons.reports.store', $this->lesson), $payload);
    $this->post(route('lessons.reports.store', $this->lesson), $payload);

    expect(ItemReport::count())->toBe(1);
});

test('an unknown item or reason is refused', function () {
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'concept:naoexiste', 'reason' => 'other'])
        ->assertSessionHasErrors('item');
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'summary', 'reason' => 'boring'])
        ->assertSessionHasErrors('reason');

    expect(ItemReport::count())->toBe(0);
});

test('undoing a report brings the item back', function () {
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'summary', 'reason' => 'other']);
    $report = ItemReport::sole();

    $this->delete(route('lessons.reports.destroy', [$this->lesson, $report]))
        ->assertSessionHas('status', 'report-withdrawn');

    expect($report->fresh()->status)->toBe(ReportStatus::Withdrawn)
        ->and($this->review->fresh()->reported)->toBeFalse();
});

test('a report can only be undone through its own lesson', function () {
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'summary', 'reason' => 'other']);
    $report = ItemReport::sole();
    $other = Lesson::factory()->forUser($this->user)->create();

    $this->delete(route('lessons.reports.destroy', [$other, $report]))->assertNotFound();

    expect($report->fresh()->status)->toBe(ReportStatus::Open);
});

test('the student edits the summary and a concept explanation', function () {
    $this->patch(route('lessons.review.update', $this->lesson), ['summary' => 'Meu resumo.'])
        ->assertSessionHas('status', 'review-updated');
    $this->patch(route('lessons.review.concepts.update', [$this->lesson, $this->concept]), ['explanation' => 'Minha explicação.']);

    $review = $this->review->fresh();

    expect($review->summary)->toBe('Meu resumo.')
        ->and($review->concepts[0]['explanation'])->toBe('Minha explicação.')
        ->and($review->concepts[0]['name'])->toBe('Present Perfect')
        ->and($review->concepts[1]['explanation'])->toBe($this->review->concepts[1]['explanation']);
});

test('an edited summary keeps the 150 word limit', function () {
    $this->patch(route('lessons.review.update', $this->lesson), ['summary' => str_repeat('palavra ', 151)])
        ->assertSessionHasErrors(['summary' => 'Use até 150 palavras.']);
});

test('removing a concept takes it and its open report out', function () {
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => LessonReview::conceptItem($this->concept), 'reason' => 'other']);

    $this->delete(route('lessons.review.concepts.destroy', [$this->lesson, $this->concept]))
        ->assertSessionHas('status', 'concept-removed');

    $review = $this->review->fresh();

    expect($review->concepts)->toHaveCount(1)
        ->and($review->hasConcept($this->concept))->toBeFalse()
        ->and(ItemReport::sole()->status)->toBe(ReportStatus::Superseded)
        ->and($review->reported)->toBeFalse();
});

test('an unknown concept is not found', function () {
    $this->patch(route('lessons.review.concepts.update', [$this->lesson, 'naoexiste']), ['explanation' => 'x'])->assertNotFound();
    $this->delete(route('lessons.review.concepts.destroy', [$this->lesson, 'naoexiste']))->assertNotFound();
});

test('the student edits title and date of the lesson', function () {
    $this->patch(route('lessons.update', $this->lesson), ['title' => 'Novo título', 'lesson_date' => '2026-10-20'])
        ->assertSessionHas('status', 'lesson-updated');

    expect($this->lesson->fresh()->title)->toBe('Novo título')
        ->and($this->lesson->fresh()->lesson_date->toDateString())->toBe('2026-10-20');
});

test('regenerating is asked by the student, queued and limited', function () {
    Queue::fake();
    config(['fixa.ai.max_regenerations_per_lesson' => 1]);

    $this->post(route('lessons.regenerate', $this->lesson))->assertRedirect(route('lessons.show', $this->lesson));

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Generating)
        ->and($this->lesson->fresh()->regenerations)->toBe(1);
    Queue::assertPushed(ProcessLesson::class, 1);

    $this->lesson->forceFill(['status' => LessonStatus::Ready])->save();

    $this->post(route('lessons.regenerate', $this->lesson))->assertSessionHasErrors('lesson');
    Queue::assertPushed(ProcessLesson::class, 1);
});

test('a lesson still processing cannot be regenerated', function () {
    Queue::fake();
    $this->lesson->forceFill(['status' => LessonStatus::Generating])->save();

    $this->post(route('lessons.regenerate', $this->lesson))->assertSessionHasErrors('lesson');

    Queue::assertNothingPushed();
});

test('regenerating replaces the edits and closes the open reports', function () {
    app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    $this->patch(route('lessons.review.update', $this->lesson), ['summary' => 'Meu resumo.']);
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'summary', 'reason' => 'other']);

    $this->post(route('lessons.regenerate', $this->lesson));

    $review = $this->review->fresh();

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Ready)
        ->and($review->summary)->toBe(AiFixtures::lessonReview()['summary'])
        ->and($review->reported)->toBeFalse()
        ->and(ItemReport::sole()->status)->toBe(ReportStatus::Superseded);
});

test('nothing regenerates a ready review on its own', function () {
    $ai = app(FakeAiProvider::class);

    app()->call([new ProcessLesson($this->lesson->id), 'handle']);
    $this->get(route('lessons.show', $this->lesson))->assertOk();

    $ai->assertSentCount(0);
    expect($this->lesson->fresh()->regenerations)->toBe(0);
});

test('retry works only for failures another attempt can fix', function () {
    Queue::fake();
    $this->lesson->forceFill(['status' => LessonStatus::Failed, 'failure_reason' => 'ai_unavailable'])->save();

    $this->post(route('lessons.retry', $this->lesson))->assertRedirect(route('lessons.show', $this->lesson));

    expect($this->lesson->fresh()->status)->toBe(LessonStatus::Queued)
        ->and($this->lesson->fresh()->regenerations)->toBe(1);
    Queue::assertPushed(ProcessLesson::class, 1);

    $this->lesson->forceFill(['status' => LessonStatus::Failed, 'failure_reason' => 'too_short'])->save();

    $this->post(route('lessons.retry', $this->lesson))->assertSessionHasErrors('lesson');
    Queue::assertPushed(ProcessLesson::class, 1);
});

test('removing a lesson deletes its review, reports and files and is audited', function () {
    Storage::fake(LessonFile::DISK);
    $path = "{$this->user->ulid}/{$this->lesson->ulid}/a.pdf";
    Storage::disk(LessonFile::DISK)->put($path, '%PDF');
    (new LessonFile)->forceFill([
        'user_id' => $this->user->id, 'lesson_id' => $this->lesson->id, 'path' => $path,
        'mime_type' => 'application/pdf', 'size_bytes' => 4, 'sha256' => str_repeat('a', 64),
    ])->save();
    $this->post(route('lessons.reports.store', $this->lesson), ['item' => 'summary', 'reason' => 'other']);

    $this->delete(route('lessons.destroy', $this->lesson))->assertRedirect(route('lessons.index'));

    expect(Lesson::withoutGlobalScopes()->count())->toBe(0)
        ->and(LessonReview::withoutGlobalScopes()->count())->toBe(0)
        ->and(ItemReport::withoutGlobalScopes()->count())->toBe(0)
        ->and(DB::table('audit_logs')->where('action', 'lesson.deleted')->exists())->toBeTrue();
    Storage::disk(LessonFile::DISK)->assertMissing($path);
});

test('the student marks what they can already do, and regenerating clears it', function () {
    $this->put(route('lessons.review.can-do', $this->lesson), ['done' => [2, 0, 0, 7]])->assertRedirect();

    expect($this->review->fresh()->can_do_done)->toBe([0, 2]);

    $this->get(route('lessons.show', $this->lesson))
        ->assertInertia(fn (Assert $page) => $page->where('review.can_do_done', [0, 2]));

    app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    $this->post(route('lessons.regenerate', $this->lesson));

    expect($this->review->fresh()->can_do_done)->toBeNull();
});
