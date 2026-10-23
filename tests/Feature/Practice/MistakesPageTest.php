<?php

use App\Domain\Practice\ErrorCategory;
use App\Domain\Practice\MistakeBook;
use App\Models\Mistake;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->mistake = function (string $point, ErrorCategory $category, array $extra = []) {
        (new Mistake)->forceFill([
            'user_id' => $this->user->id, 'category' => $category, 'point_key' => MistakeBook::pointKey($point),
            'content' => ['point' => $point, 'answer' => 'I work here since 2021.', 'corrected' => "I've worked here since 2021.", 'explanation' => 'Com since, use o Present Perfect.'],
            'last_error_at' => now(), ...$extra,
        ])->save();
    };
    $this->actingAs($this->user);
});

test('the notebook lists open mistakes by priority with repetitions, next review and streak (J4-F6)', function () {
    ($this->mistake)('since', ErrorCategory::Grammar, ['due_at' => '2026-10-27 03:00:00', 'step' => 1, 'correct_streak' => 1, 'repetitions' => 2]);
    ($this->mistake)('for', ErrorCategory::Preposition, ['due_at' => '2026-10-25 03:00:00', 'priority' => true, 'repetitions' => 3]);
    ($this->mistake)('resolvido', ErrorCategory::Spelling, ['resolved_at' => now()]);

    $this->get(route('practice.mistakes'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Mistakes')
            ->where('counts', ['all' => 2, 'grammar' => 1, 'vocabulary' => 0, 'preposition' => 1, 'spelling' => 0])
            ->has('mistakes', 2)
            ->where('mistakes.0.category', 'preposition')
            ->where('mistakes.0.priority', true)
            ->where('mistakes.0.due_in_days', 1)
            ->where('mistakes.1.due_in_days', 3)
            ->where('mistakes.1.correct_streak', 1)
            ->where('mistakes.1.answer', 'I work here since 2021.')
            ->missing('mistakes.0.point_key'));
});

test('the category filter comes from the URL', function () {
    ($this->mistake)('since', ErrorCategory::Grammar, ['due_at' => now()]);
    ($this->mistake)('for', ErrorCategory::Preposition, ['due_at' => now()]);

    $this->get(route('practice.mistakes', ['categoria' => 'grammar']))
        ->assertInertia(fn (Assert $page) => $page->where('filter', 'grammar')->has('mistakes', 1)->where('counts.all', 2));
});

test('another student never sees my notebook', function () {
    ($this->mistake)('since', ErrorCategory::Grammar, ['due_at' => now()]);

    $this->actingAs(User::factory()->create())
        ->get(route('practice.mistakes'))
        ->assertInertia(fn (Assert $page) => $page->where('counts.all', 0)->has('mistakes', 0));
});
