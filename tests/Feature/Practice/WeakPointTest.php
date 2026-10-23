<?php

use App\Domain\Practice\ErrorCategory;
use App\Domain\Practice\ItemType;
use App\Domain\Practice\MistakeBook;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\WeakPointBuilder;
use App\Models\Mistake;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC'));
    $this->user = User::factory()->create();
    $this->mistake = function (string $point, array $extra = []) {
        $mistake = (new Mistake)->forceFill([
            'user_id' => $this->user->id,
            'category' => ErrorCategory::Preposition,
            'point_key' => MistakeBook::pointKey($point),
            'content' => [
                'point' => $point, 'answer' => "I've lived here since six years.",
                'corrected' => "I've lived here for six years.", 'natural' => "I've lived here for six years.",
                'explanation' => 'For mais duração.',
            ],
            'repetitions' => 1,
            'due_at' => now()->subHour(),
            'last_error_at' => now()->subDay(),
            ...$extra,
        ]);
        $mistake->save();

        return $mistake;
    };
    $this->builder = app(WeakPointBuilder::class);
});

test('due mistakes become choice items with a fixed key, priority first (PLA-02)', function () {
    ($this->mistake)('for e since');
    ($this->mistake)('since + ponto', ['priority' => true, 'due_at' => now()->subMinute()]);

    $session = $this->builder->for($this->user);
    $items = $session->items()->get();

    expect($session->kind)->toBe(SessionKind::WeakPoint)
        ->and($items)->toHaveCount(2)
        ->and($items[0]->type)->toBe(ItemType::Choice)
        ->and($items[0]->content['point'])->toBe('since + ponto')
        ->and($items[0]->content['options'])->toContain("I've lived here for six years.", "I've lived here since six years.")
        ->and($items[0]->content['accepted'])->toBe(["I've lived here for six years."])
        ->and($items[0]->content['category'])->toBe('preposition');
});

test('mistakes not due yet or already out of the list are left out', function () {
    ($this->mistake)('for e since', ['due_at' => now()->addDay()]);
    ($this->mistake)('already', ['resolved_at' => now()]);

    expect($this->builder->for($this->user))->toBeNull();
});

test('at most 5 items, and an open session is reused', function () {
    foreach (range(1, 7) as $index) {
        ($this->mistake)("ponto {$index}");
    }

    $first = $this->builder->for($this->user);

    expect($first->items()->count())->toBe(5)
        ->and($this->builder->for($this->user)->id)->toBe($first->id);
});

test('answering the weak point right moves the mistake forward, wrong keeps it', function () {
    $mistake = ($this->mistake)('for e since');
    $item = $this->builder->for($this->user)->items()->first();
    $this->actingAs($this->user);

    $this->postJson(route('practice.attempts.store', $item), ['answer' => "I've lived here for six years."])
        ->assertJson(['verdict' => 'total', 'xp' => 4]);

    expect($mistake->fresh()->correct_streak)->toBe(1)
        ->and($mistake->fresh()->due_at->toIso8601String())->toBe('2026-10-27T03:00:00+00:00');
});

test('a wrong weak point answer counts another repetition in the same category', function () {
    $mistake = ($this->mistake)('for e since');
    $item = $this->builder->for($this->user)->items()->first();
    $this->actingAs($this->user);

    $this->postJson(route('practice.attempts.store', $item), ['answer' => "I've lived here since six years."])
        ->assertJson(['verdict' => 'wrong', 'xp' => 1]);

    expect(Mistake::count())->toBe(1)
        ->and($mistake->fresh()->repetitions)->toBe(2);
});
