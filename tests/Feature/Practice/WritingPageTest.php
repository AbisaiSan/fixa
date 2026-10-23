<?php

use App\Domain\Practice\ErrorCategory;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\MistakeBook;
use App\Domain\Practice\SessionKind;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create(['lesson_date' => '2026-10-21']);
    $this->session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $this->lesson->id, 'kind' => SessionKind::Writing]);
    $this->session->save();
    $this->items = collect(AiFixtures::activities()['items'])->map(function (array $raw, int $index) {
        $item = (new PracticeItem)->forceFill([
            'user_id' => $this->user->id, 'practice_session_id' => $this->session->id, 'position' => $index + 1,
            'type' => ItemType::from($raw['type']), 'context' => $raw['context'],
            'content' => collect($raw)->except(['type', 'context'])->all(),
        ]);
        $item->save();

        return $item;
    });
    $this->actingAs($this->user);
});

test('the writing page never sends the answer key before the answer (J4-F2)', function () {
    $response = $this->get(route('practice.writing', $this->session));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('practice/Writing')
        ->where('session.kind', 'writing')
        ->where('session.lesson.lesson_date', '2026-10-21')
        ->has('items', 5)
        ->where('items.0.sentence', 'I ___ (work) at this company since 2021.')
        ->where('items.0.attempt', null)
        ->missing('items.0.accepted')
        ->missing('items.0.corrected')
        ->missing('items.0.content'));

    expect($response->getContent())->not->toContain('have been working')->not->toContain('since 2021.","natural');
});

test('answered items come back with their correction', function () {
    $this->postJson(route('practice.attempts.store', $this->items[0]), ['answer' => 'worked']);

    $this->get(route('practice.writing', $this->session))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.attempt.verdict', 'wrong')
            ->where('items.0.attempt.answer', 'worked')
            ->where('items.0.attempt.correction.corrected', 'I have worked at this company since 2021.'));
});

test('the weak point page builds the session from the notebook', function () {
    (new Mistake)->forceFill([
        'user_id' => $this->user->id, 'category' => ErrorCategory::Preposition, 'point_key' => MistakeBook::pointKey('for e since'),
        'content' => ['point' => 'for e since', 'answer' => 'since six years', 'corrected' => 'for six years', 'explanation' => 'Duração pede for.'],
        'due_at' => now()->subHour(), 'last_error_at' => now(),
    ])->save();

    $this->get(route('practice.weak-point'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('practice/Writing')
            ->where('session.kind', 'weak_point')
            ->where('session.lesson', null)
            ->where('items.0.type', 'choice')
            ->has('items.0.options', 2));
});

test('with nothing due the weak point goes back to practice', function () {
    $this->get(route('practice.weak-point'))->assertRedirect(route('practice'));

    $this->get(route('practice'))->assertInertia(fn (Assert $page) => $page->where('flash', 'weak-point-empty'));
});

test('a correction can be reported (MAT-05)', function () {
    $this->post(route('practice.items.reports.store', $this->items[2]), ['reason' => 'confusing_explanation'])
        ->assertSessionHas('status', 'item-reported');

    expect($this->items[2]->fresh()->reported)->toBeTrue()
        ->and(ItemReport::sole()->reportable_type)->toBe('practice_item')
        ->and(ItemReport::sole()->source_lesson_id)->toBe($this->lesson->id);
});

test('a reading session is not opened as writing', function () {
    $reading = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'kind' => SessionKind::Reading]);
    $reading->save();

    $this->get(route('practice.writing', $reading))->assertNotFound();
});
