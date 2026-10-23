<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('the settings page shows study, preferences and two factor state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('settings/Index')
        ->where('study.daily_goal_minutes', 10)
        ->where('study.target_language', 'en')
        ->where('study.timezone', 'America/Sao_Paulo')
        ->where('goalRange', ['min' => 5, 'max' => 30, 'step' => 5])
        ->where('languages.available', ['en'])
        ->where('languages.upcoming', ['es', 'fr'])
        ->where('preferences.theme', 'system')
        ->where('twoFactor.enabled', false)
        ->where('twoFactor.required', false)
        ->has('timezones'));
});

test('admins see two factor as required', function () {
    $admin = User::factory()->create();
    $admin->role = Role::Admin;
    $admin->save();

    $this->actingAs($admin)->get(route('settings'))
        ->assertInertia(fn (Assert $page) => $page->where('twoFactor.required', true));
});

test('the settings page requires a verified student', function () {
    $this->get(route('settings'))->assertRedirect(route('login'));
});

test('the name can be changed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from(route('settings'))
        ->patch(route('profile.update'), ['name' => 'Marina Alves', 'email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('settings'));

    expect($user->fresh()->name)->toBe('Marina Alves')
        ->and($user->fresh()->email_verified_at)->not->toBeNull();
});

test('a new email needs confirmation again', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => 'novo@example.com']);

    $user = $user->fresh();

    expect($user->email)->toBe('novo@example.com')
        ->and($user->email_verified_at)->toBeNull();
    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the password changes with the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'senha5678',
        'password_confirmation' => 'senha5678',
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'password-updated');

    expect(Hash::check('senha5678', $user->fresh()->password))->toBeTrue();
});

test('the password does not change with a wrong current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'errada',
        'password' => 'senha5678',
        'password_confirmation' => 'senha5678',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
