<?php

use App\Models\AuditLogEntry;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

function actions(): array
{
    return AuditLogEntry::orderBy('id')->pluck('action')->all();
}

test('a successful login is recorded with the student and ip', function () {
    $user = User::factory()->create();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])
        ->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $entry = AuditLogEntry::where('action', 'auth.login')->sole();

    expect($entry->user_id)->toBe($user->id)
        ->and($entry->ip_address)->toBe('198.51.100.4');
});

test('a failed login is recorded without storing the email', function () {
    $user = User::factory()->create(['email' => 'marina@example.com']);

    $this->post(route('login'), ['email' => 'marina@example.com', 'password' => 'errada']);

    $entry = AuditLogEntry::where('action', 'auth.login_failed')->sole();

    expect($entry->user_id)->toBe($user->id)
        ->and($entry->metadata['account'])->toHaveLength(16)
        ->and(json_encode($entry->metadata))->not->toContain('marina');
});

test('repeated failures leave the same fingerprint and a lockout', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('login'), ['email' => 'Alguem@Example.com', 'password' => 'errada']);
    }

    $fingerprints = AuditLogEntry::where('action', 'auth.login_failed')->get()->pluck('metadata.account')->unique();

    expect($fingerprints)->toHaveCount(1)
        ->and(actions())->toContain('auth.lockout');
});

test('logout and password change are recorded', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'nova-senha-segura-123',
        'password_confirmation' => 'nova-senha-segura-123',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('logout'));

    expect(actions())->toContain('auth.password_changed', 'auth.logout');
});

test('turning two factor on and off is recorded', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $this->post(route('two-factor.enable'));
    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
    $this->post(route('two-factor.confirm'), ['code' => $code]);
    $this->delete(route('two-factor.disable'));

    expect(actions())->toContain('auth.two_factor_enabled', 'auth.two_factor_confirmed', 'auth.two_factor_disabled');
});

test('promoting an admin is recorded', function () {
    $user = User::factory()->create();

    $this->artisan('fixa:admin', ['email' => $user->email]);

    $entry = AuditLogEntry::where('action', 'admin.role_changed')->sole();

    expect($entry->metadata)->toBe(['target' => $user->ulid, 'role' => 'admin', 'via' => 'console']);
});

test('the trail survives account deletion without pointing to it', function () {
    $user = User::factory()->create();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $user->delete();

    expect(AuditLogEntry::where('action', 'auth.login')->sole()->user_id)->toBeNull();
});
