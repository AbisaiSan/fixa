<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Models\Lesson;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

/**
 * Fluxo A do protótipo (J2-F6): do Hoje vazio até a revisão pronta e
 * "Praticar esta aula". A fila roda na hora e a IA é sempre a falsa.
 */
test('a new student goes from an empty Today to a ready review and practice', function () {
    $user = User::factory()->create();
    app(FakeAiProvider::class)
        ->respondWith(AiTask::LessonReview, AiFixtures::lessonReview())
        ->respondWith(AiTask::Cards, AiFixtures::cards())
        ->respondWith(AiTask::Activities, AiFixtures::activities());

    $this->actingAs($user)->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page->component('app/Today')->where('status', 'empty'));

    $this->get(route('lessons.create'))->assertOk();

    $response = $this->post(route('lessons.store'), [
        'source' => 'text',
        'title' => 'Present Perfect no trabalho',
        'lesson_date' => '2026-10-21',
        'rights' => '1',
        'text' => str_repeat('I have worked here since 2022. ', 12),
    ]);

    $lesson = Lesson::sole();
    $response->assertRedirect(route('lessons.show', $lesson));

    $this->get(route('lessons.show', $lesson))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lesson.status', 'ready')
            ->where('review.summary', AiFixtures::lessonReview()['summary']));

    $this->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('status', 'ready')
            ->where('latestLesson.ulid', $lesson->ulid)
            ->where('latestLesson.status', 'ready'));

    $this->get(route('practice'))
        ->assertInertia(fn (Assert $page) => $page->component('app/Practice')->where('status', 'ready')->where('flashcards.new', 3));

    // "Praticar esta aula" abre os flashcards da aula.
    $this->get(route('flashcards', ['aula' => $lesson->ulid]))
        ->assertInertia(fn (Assert $page) => $page->component('practice/Flashcards')->has('cards', 3));
});
