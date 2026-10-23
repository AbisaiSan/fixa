<?php

use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // 15h de 24/10 em São Paulo.
    Date::setTestNow(CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->call = fn (string $task, string $at) => DB::table('ai_calls')->insert([
        'user_id' => $this->user->id, 'provider' => 'fake', 'model' => 'fake-model', 'task' => $task,
        'prompt_version' => 'v1', 'outcome' => 'ok', 'created_at' => $at,
    ]);
});

afterEach(fn () => Date::setTestNow());

test('every page carries the AI counter of the day (ATV-05)', function () {
    ($this->call)('text_correction', '2026-10-24 12:00:00');
    ($this->call)('word_details', '2026-10-24 13:00:00');
    // Ontem no fuso do aluno e tarefas da aula não contam.
    ($this->call)('text_correction', '2026-10-24 02:00:00');
    ($this->call)('lesson_review', '2026-10-24 12:00:00');

    $this->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page->where('aiLimit', [
            'used' => 2,
            'limit' => 30,
            'left' => 28,
            'reached' => false,
            'resets_at' => '2026-10-25T00:00:00-03:00',
        ]));
});

test('at the limit the counter says so and flashcards still work', function () {
    config(['fixa.ai.daily_student_limit' => 1]);
    ($this->call)('text_correction', '2026-10-24 12:00:00');
    $card = Card::factory()->forLesson(Lesson::factory()->forUser($this->user)->create())->create();

    $this->get(route('practice'))->assertInertia(fn (Assert $page) => $page->where('aiLimit.reached', true)->where('aiLimit.left', 0));
    $this->postJson(route('cards.reviews.store', $card), ['rating' => 3])->assertOk();
});
