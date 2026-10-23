<?php

use App\Models\User;

$study = fn (array $overrides = []) => [
    'daily_goal_minutes' => 15,
    'target_language' => 'en',
    'timezone' => 'America/Manaus',
    ...$overrides,
];

test('the student updates goal and timezone', function () use ($study) {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('preferences.study'), $study())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'study-preferences-updated');

    $enrollment = $user->fresh()->activeEnrollment;

    expect($enrollment->daily_goal_minutes)->toBe(15)
        ->and($enrollment->timezone)->toBe('America/Manaus');
});

test('the daily goal stays between 5 and 30 minutes', function (int $goal) use ($study) {
    $this->actingAs(User::factory()->create())
        ->patch(route('preferences.study'), $study(['daily_goal_minutes' => $goal]))
        ->assertSessionHasErrors('daily_goal_minutes');
})->with([4, 31, 0]);

test('only available languages can be studied', function () use ($study) {
    $this->actingAs(User::factory()->create())
        ->patch(route('preferences.study'), $study(['target_language' => 'es']))
        ->assertSessionHasErrors('target_language');
});

test('the timezone must be valid', function () use ($study) {
    $this->actingAs(User::factory()->create())
        ->patch(route('preferences.study'), $study(['timezone' => 'Marte/Base']))
        ->assertSessionHasErrors('timezone');
});

test('the student updates theme and notifications', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('preferences.account'), [
        'theme' => 'dark',
        'notify_daily_reminder' => true,
        'notify_weekly_summary' => false,
        'notify_streak' => true,
    ])->assertSessionHasNoErrors()->assertCookie('appearance', 'dark', encrypted: false);

    $user = $user->fresh();

    expect($user->theme)->toBe('dark')
        ->and($user->notify_daily_reminder)->toBeTrue()
        ->and($user->notify_streak)->toBeTrue();

    $this->actingAs($user)->get(route('today'))->assertSee('data-theme="dark"', false);
});

test('an unknown theme is rejected', function () {
    $this->actingAs(User::factory()->create())->patch(route('preferences.account'), [
        'theme' => 'neon',
        'notify_daily_reminder' => false,
        'notify_weekly_summary' => false,
        'notify_streak' => false,
    ])->assertSessionHasErrors('theme');
});

test('guests cannot change preferences', function () use ($study) {
    $this->patch(route('preferences.study'), $study())->assertRedirect(route('login'));
});
