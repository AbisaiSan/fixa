<?php

use App\Enums\Role;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

function enableTwoFactor(User $user): array
{
    $codes = ['code-one-1111', 'code-two-2222'];

    $user->forceFill([
        'two_factor_secret' => encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $codes;
}

function currentCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
}

test('turning two factor on requires a recent password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('two factor is only active after confirming a valid code', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $this->post(route('two-factor.enable'))->assertSessionHasNoErrors();
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();

    $this->post(route('two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors();
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();

    $this->post(route('two-factor.confirm'), ['code' => currentCode($user)])->assertSessionHasNoErrors();
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

test('turning two factor off requires a recent password confirmation', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->actingAs($user)
        ->delete(route('two-factor.disable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

describe('login with two factor', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->codes = enableTwoFactor($this->user);

        $this->post(route('login'), ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    });

    test('accepts the authenticator code', function () {
        $this->post(route('two-factor.login.store'), ['code' => currentCode($this->user)]);

        $this->assertAuthenticatedAs($this->user);
    });

    test('rejects a wrong code', function () {
        $this->post(route('two-factor.login.store'), ['code' => '123456']);

        $this->assertGuest();
    });

    test('accepts a recovery code only once', function () {
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'code-one-1111']);

        $this->assertAuthenticatedAs($this->user);
        expect($this->user->fresh()->recoveryCodes())->not->toContain('code-one-1111')
            ->and($this->user->fresh()->recoveryCodes())->toHaveCount(2);
    });
});

describe('admins', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create();
        $this->admin->role = Role::Admin;
        $this->admin->save();
    });

    test('without two factor are sent to the security settings', function () {
        $this->actingAs($this->admin)
            ->get(route('today'))
            ->assertRedirect(route('security.edit'));
    });

    test('without two factor can still turn it on and log out', function () {
        $this->actingAs($this->admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk();

        $this->actingAs($this->admin)->post(route('logout'))->assertRedirect();
    });

    test('with two factor use the app normally', function () {
        enableTwoFactor($this->admin);

        $this->actingAs($this->admin)->get(route('today'))->assertOk();
    });
});

test('students are not forced to use two factor', function () {
    $this->actingAs(User::factory()->create())->get(route('today'))->assertOk();
});
