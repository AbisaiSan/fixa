<?php

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('registration creates an enrollment with the default study settings', function () {
    $this->post(route('register.store'), [
        'name' => 'Marina Alves',
        'email' => 'marina@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'terms' => true,
    ]);

    $enrollment = User::where('email', 'marina@example.com')->firstOrFail()->activeEnrollment;

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->target_language)->toBe('en')
        ->and($enrollment->instruction_language)->toBe('pt-BR')
        ->and($enrollment->daily_goal_minutes)->toBe(10)
        ->and($enrollment->timezone)->toBe('America/Sao_Paulo')
        ->and($enrollment->ulid)->toHaveLength(26);
});

test('the shared target language comes from the active enrollment', function () {
    $user = User::factory()->create();
    $user->activeEnrollment->forceFill(['target_language' => 'es'])->save();

    $this->actingAs($user)->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page->where('targetLang.code', 'es'));
});

test('enrollments are isolated between students', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $this->actingAs($alice);

    expect(Enrollment::pluck('user_id')->all())->toBe([$alice->id])
        ->and(Enrollment::where('ulid', $bob->activeEnrollment->ulid)->exists())->toBeFalse();
});

test('the student day follows the enrollment timezone', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-24 01:30:00', 'UTC'));

    $enrollment = User::factory()->create()->activeEnrollment;

    // 01:30 UTC ainda é dia 23 em São Paulo (UTC-3).
    expect($enrollment->today()->toDateString())->toBe('2026-10-23');

    Carbon::setTestNow();
});

test('a new user has opt-in notifications and the system theme', function () {
    $user = User::factory()->create()->fresh();

    expect($user->theme)->toBe('system')
        ->and($user->notify_daily_reminder)->toBeFalse()
        ->and($user->notify_weekly_summary)->toBeFalse()
        ->and($user->notify_streak)->toBeFalse();
});
