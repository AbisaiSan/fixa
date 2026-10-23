<?php

use App\Domain\Practice\ErrorCategory;
use App\Domain\Practice\MistakeBook;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create(['title' => 'Present Perfect no trabalho']);
    $this->session = function (SessionKind $kind, array $extra = []) {
        $session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $this->lesson->id, 'kind' => $kind, ...$extra]);
        $session->save();

        return $session;
    };
    $this->actingAs($this->user);
});

test('the hub shows writing, reading, weak point and notebook from the lessons (J4-F1)', function () {
    $writing = ($this->session)(SessionKind::Writing);
    foreach (range(1, 6) as $position) {
        (new PracticeItem)->forceFill(['user_id' => $this->user->id, 'practice_session_id' => $writing->id, 'position' => $position, 'type' => 'fill', 'content' => []])->save();
    }
    $reading = AiFixtures::reading();
    ($this->session)(SessionKind::Reading, ['content' => ['title' => $reading['title'], 'paragraphs' => $reading['paragraphs'], 'glossary' => []]]);
    foreach (['for e since' => true, 'ever' => false] as $point => $priority) {
        (new Mistake)->forceFill([
            'user_id' => $this->user->id, 'category' => ErrorCategory::Grammar, 'point_key' => MistakeBook::pointKey($point),
            'content' => ['point' => $point, 'answer' => 'a', 'corrected' => 'b', 'explanation' => 'c'],
            'priority' => $priority, 'due_at' => now()->subHour(), 'last_error_at' => now(),
        ])->save();
    }

    $this->get(route('practice'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Practice')
            ->where('writing.ulid', $writing->ulid)
            ->where('writing.lesson', 'Present Perfect no trabalho')
            ->where('writing.items', 6)
            ->where('writing.completed', false)
            ->where('reading.title', "Ana's interview")
            ->where('reading.words', 102)
            ->where('weakPoint', ['due' => 2, 'point' => 'for e since'])
            ->where('mistakes', ['open' => 2, 'priority' => 1]));
});

test('the hub prefers a writing session still to do', function () {
    $todo = ($this->session)(SessionKind::Writing);
    ($this->session)(SessionKind::Writing, ['status' => SessionStatus::Completed]);

    $this->get(route('practice'))->assertInertia(fn (Assert $page) => $page->where('writing.ulid', $todo->ulid));
});
