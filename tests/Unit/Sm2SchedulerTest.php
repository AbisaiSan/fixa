<?php

use App\Domain\Cards\Scheduling\CardState;
use App\Domain\Cards\Scheduling\Rating;
use App\Domain\Cards\Scheduling\Sm2Scheduler;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->scheduler = new Sm2Scheduler;
    // 15h em São Paulo (18h UTC).
    $this->now = CarbonImmutable::parse('2026-10-24 18:00:00', 'UTC');
    $this->tz = 'America/Sao_Paulo';
});

function reviewed(int $repetitions, int $interval, float $ease = 2.5, int $lapses = 0): CardState
{
    return new CardState(repetitions: $repetitions, intervalDays: $interval, ease: $ease, lapses: $lapses);
}

test('each button sets the next interval and ease', function (CardState $state, Rating $rating, int $interval, float $ease, int $repetitions) {
    $next = $this->scheduler->schedule($state, $rating, $this->now, $this->tz);

    expect($next->intervalDays)->toBe($interval)
        ->and($next->ease)->toBe($ease)
        ->and($next->repetitions)->toBe($repetitions);
})->with([
    'novo, difícil' => [CardState::new(), Rating::Hard, 1, 2.35, 1],
    'novo, bom' => [CardState::new(), Rating::Good, 2, 2.5, 1],
    'novo, fácil' => [CardState::new(), Rating::Easy, 4, 2.65, 1],
    'segunda, difícil' => [reviewed(1, 2), Rating::Hard, 3, 2.35, 2],
    'segunda, bom' => [reviewed(1, 2), Rating::Good, 6, 2.5, 2],
    'segunda, fácil' => [reviewed(1, 2), Rating::Easy, 8, 2.65, 2],
    'madura, difícil' => [reviewed(3, 10), Rating::Hard, 12, 2.35, 4],
    'madura, bom' => [reviewed(3, 10), Rating::Good, 25, 2.5, 4],
    'madura, fácil' => [reviewed(3, 10), Rating::Easy, 35, 2.65, 4],
]);

test('a miss restarts the card, comes back in a minute and lowers the ease', function () {
    $next = $this->scheduler->schedule(reviewed(4, 30, 2.5, 1), Rating::Again, $this->now, $this->tz);

    expect($next->repetitions)->toBe(0)
        ->and($next->intervalDays)->toBe(0)
        ->and($next->lapses)->toBe(2)
        ->and($next->ease)->toBe(2.3)
        ->and($next->dueAt?->equalTo($this->now->addMinute()))->toBeTrue();
});

test('after a miss the card grows again from the start', function () {
    $missed = $this->scheduler->schedule(reviewed(4, 30), Rating::Again, $this->now, $this->tz);
    $next = $this->scheduler->schedule($missed, Rating::Good, $this->now->addMinutes(2), $this->tz);

    expect($next->intervalDays)->toBe(2)->and($next->repetitions)->toBe(1);
});

test('the ease never goes below 1.3', function () {
    $next = $this->scheduler->schedule(reviewed(3, 10, 1.35), Rating::Hard, $this->now, $this->tz);

    expect($next->ease)->toBe(1.3);
});

test('intervals never pass one year', function () {
    $next = $this->scheduler->schedule(reviewed(9, 300, 2.5), Rating::Easy, $this->now, $this->tz);

    expect($next->intervalDays)->toBe(365);
});

test('harder buttons never give a longer interval', function (CardState $state) {
    $preview = $this->scheduler->preview($state, $this->now, $this->tz);

    expect($preview[Rating::Hard->value])->toBeLessThan($preview[Rating::Good->value])
        ->and($preview[Rating::Good->value])->toBeLessThan($preview[Rating::Easy->value]);
})->with([
    'novo' => [CardState::new()],
    'segunda' => [reviewed(1, 2)],
    'madura' => [reviewed(3, 10)],
    'fácil demais' => [reviewed(2, 2, 1.3)],
]);

test('the preview shows the interval of each of the four buttons', function () {
    $preview = $this->scheduler->preview(reviewed(3, 10), $this->now, $this->tz);

    expect($preview)->toBe([
        Rating::Again->value => 0,
        Rating::Hard->value => 12,
        Rating::Good->value => 25,
        Rating::Easy->value => 35,
    ]);
});

test('the card is due at the start of the day in the student timezone', function () {
    $next = $this->scheduler->schedule(CardState::new(), Rating::Good, $this->now, $this->tz);

    // 24/10 + 2 dias = 26/10, 0h em São Paulo, 3h UTC.
    expect($next->dueAt?->toIso8601String())->toBe('2026-10-26T03:00:00+00:00');
});

test('a review late at night still counts for that day', function () {
    // 23h50 de 24/10 em São Paulo, já 25/10 em UTC.
    $late = CarbonImmutable::parse('2026-10-25 02:50:00', 'UTC');

    $next = $this->scheduler->schedule(CardState::new(), Rating::Hard, $late, $this->tz);

    expect($next->dueAt?->toIso8601String())->toBe('2026-10-25T03:00:00+00:00');
});

test('just after midnight the new day starts the count', function () {
    // 0h10 de 25/10 em São Paulo.
    $early = CarbonImmutable::parse('2026-10-25 03:10:00', 'UTC');

    $next = $this->scheduler->schedule(CardState::new(), Rating::Hard, $early, $this->tz);

    expect($next->dueAt?->toIso8601String())->toBe('2026-10-26T03:00:00+00:00');
});
