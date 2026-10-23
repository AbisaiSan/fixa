<?php

use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\LessonStatus;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the lessons list shows origin, date, status and what was generated', function () {
    $ready = Lesson::factory()->forUser($this->user)->status(LessonStatus::Ready)->create(['lesson_date' => '2026-10-21']);
    LessonReview::factory()->for($ready)->create();
    $pdf = Lesson::factory()->forUser($this->user)->status(LessonStatus::Generating)
        ->create(['lesson_date' => '2026-10-14', 'source' => LessonSource::Pdf, 'title' => 'Phrasal verbs']);
    (new LessonFile)->forceFill([
        'user_id' => $this->user->id, 'lesson_id' => $pdf->id, 'path' => 'x.pdf', 'pages' => 4,
        'mime_type' => 'application/pdf', 'size_bytes' => 10, 'sha256' => str_repeat('a', 64),
    ])->save();

    $this->get(route('lessons.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Lessons')
            ->where('status', 'ready')
            ->has('lessons', 2)
            ->where('lessons.0.ulid', $ready->ulid)
            ->where('lessons.0.status', 'ready')
            ->where('lessons.0.concepts', 2)
            ->where('lessons.0.source', 'text')
            ->where('lessons.1.lesson_date', '2026-10-14')
            ->where('lessons.1.source', 'pdf')
            ->where('lessons.1.pages', 4)
            ->where('lessons.1.concepts', null)
            ->missing('lessons.0.extracted_text')
            ->missing('lessons.0.id'));
});

test('the list never shows lessons of another student', function () {
    Lesson::factory()->create(['title' => 'Aula de outra pessoa']);

    $this->get(route('lessons.index'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'empty')->has('lessons', 0));
});
