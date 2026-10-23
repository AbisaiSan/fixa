<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Domain\Practice\Verdict;
use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->user = User::factory()->create();
    $lesson = Lesson::factory()->forUser($this->user)->create();
    $this->session = (new PracticeSession)->forceFill(['user_id' => $this->user->id, 'source_lesson_id' => $lesson->id, 'kind' => SessionKind::Writing]);
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

test('an answer gets its result, correction and proportional XP (ATV-03)', function () {
    $this->postJson(route('practice.attempts.store', $this->items[0]), ['answer' => 'have workd'])
        ->assertOk()
        ->assertJson([
            'item' => $this->items[0]->ulid,
            'verdict' => 'partial',
            'xp' => 6,
            'correction' => ['corrected' => 'I have worked at this company since 2021.', 'source' => 'key'],
        ]);

    $attempt = Attempt::sole();

    expect($attempt->verdict)->toBe(Verdict::Partial)
        ->and($attempt->answer)->toBe('have workd')
        ->and($this->session->fresh()->status)->toBe(SessionStatus::InProgress)
        ->and($this->session->fresh()->started_at)->not->toBeNull();
});

test('the student answer and the correction are encrypted at rest', function () {
    $this->postJson(route('practice.attempts.store', $this->items[0]), ['answer' => 'have worked']);

    $raw = (array) DB::table('attempts')->first();

    expect($raw['answer'])->not->toContain('have worked')
        ->and($raw['correction'])->not->toContain('since 2021');
});

test('the session is completed when every item is answered', function () {
    app(FakeAiProvider::class)->respondWith(AiTask::TextCorrection,
        ['verdict' => 'total', 'error' => '', 'category' => '', 'explanation' => 'Certo.', 'corrected' => 'x', 'natural' => 'y', 'point' => ''],
        ['verdict' => 'wrong', 'error' => 'visit', 'category' => 'grammar', 'explanation' => 'Use o Present Perfect.', 'corrected' => 'x', 'natural' => 'y', 'point' => 'so far'],
    );
    $answers = ['have worked', "I've never worked in sales", 'My biggest achievement has been a migration.', 'ever', 'So far I visit Rome.'];

    foreach ($this->items as $index => $item) {
        $this->postJson(route('practice.attempts.store', $item), ['answer' => $answers[$index]])->assertOk();
    }

    expect($this->session->fresh()->status)->toBe(SessionStatus::Completed)
        ->and($this->session->fresh()->completed_at)->not->toBeNull()
        ->and(Attempt::sum('xp'))->toBe(10 + 10 + 10 + 10 + 2);
});

test('sending the same item again returns the first correction without a new AI call', function () {
    $ai = app(FakeAiProvider::class)->respondWith(AiTask::TextCorrection,
        ['verdict' => 'total', 'error' => '', 'category' => '', 'explanation' => 'Certo.', 'corrected' => 'x', 'natural' => 'y', 'point' => '']);

    $this->postJson(route('practice.attempts.store', $this->items[4]), ['answer' => 'So far I have visited Rome.']);
    $this->postJson(route('practice.attempts.store', $this->items[4]), ['answer' => 'outra coisa'])
        ->assertJson(['verdict' => 'total']);

    expect(Attempt::count())->toBe(1);
    $ai->assertSentCount(1, AiTask::TextCorrection);
});

test('at the daily limit only AI corrections stop (ATV-05)', function () {
    config(['fixa.ai.daily_student_limit' => 0]);

    $this->postJson(route('practice.attempts.store', $this->items[4]), ['answer' => 'So far I visit Rome.'])
        ->assertStatus(429)
        ->assertJson(['error' => 'ai_limit_reached']);
    $this->postJson(route('practice.attempts.store', $this->items[3]), ['answer' => 'ever'])
        ->assertOk()
        ->assertJson(['verdict' => 'total']);

    expect(Attempt::count())->toBe(1);
});

test('an empty answer is refused', function () {
    $this->postJson(route('practice.attempts.store', $this->items[0]), ['answer' => ''])->assertUnprocessable();
});
