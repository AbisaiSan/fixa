<?php

use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Models\Lesson;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use App\Models\Word;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $lesson = Lesson::factory()->forUser($this->user)->create(['lesson_date' => '2026-10-21']);
    $reading = AiFixtures::reading();
    $this->session = (new PracticeSession)->forceFill([
        'user_id' => $this->user->id, 'source_lesson_id' => $lesson->id, 'kind' => SessionKind::Reading,
        'content' => ['title' => $reading['title'], 'paragraphs' => $reading['paragraphs'], 'glossary' => $reading['glossary']],
    ]);
    $this->session->save();
    $question = $reading['questions'][0];
    (new PracticeItem)->forceFill([
        'user_id' => $this->user->id, 'practice_session_id' => $this->session->id, 'position' => 1, 'type' => ItemType::Choice,
        'content' => ['sentence' => $question['question'], 'options' => $question['options'], 'accepted' => ['For two years'], 'corrected' => 'For two years', 'natural' => 'For two years', 'why' => ''],
    ])->save();
    $this->actingAs($this->user);
});

test('the reading page brings the text, the glossary with known words and the questions without the key (J4-F5)', function () {
    Word::factory()->create(['user_id' => $this->user->id, 'term' => 'to apply for', 'translation' => 'candidatar-se a']);

    $response = $this->get(route('practice.reading', $this->session));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('app/Reading')
        ->where('session.title', "Ana's interview")
        ->has('session.paragraphs', 3)
        ->where('glossary.0.form', 'applied')
        ->where('glossary.0.known', true)
        ->where('glossary.1.known', false)
        ->where('questions.0.options', ['For one year', 'For two years', 'Since last month'])
        ->where('questions.0.attempt', null)
        ->missing('questions.0.accepted'));
});
