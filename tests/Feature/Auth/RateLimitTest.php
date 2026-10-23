<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ViewErrorBag;

/**
 * Primeira mensagem de erro do campo. A sessão usa serialização JSON, então
 * depois de relida a bolsa de erros volta como array.
 */
function sessionError(string $field): string
{
    $errors = session('errors');

    if ($errors instanceof ViewErrorBag) {
        return (string) $errors->first($field);
    }

    return (string) ($errors['default']['messages'][$field][0] ?? '');
}

function failLogin(string $email, string $ip = '10.0.0.1')
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post(route('login'), ['email' => $email, 'password' => 'errada']);
}

test('login is blocked after five failures for the same email and released after the window', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        failLogin($user->email)->assertSessionHasErrors(['email' => 'E-mail ou senha não conferem. Confira e tente de novo.']);
    }

    failLogin($user->email)->assertSessionHasErrors('email');
    expect(sessionError('email'))->toStartWith('Muitas tentativas.');

    // Nem a senha certa entra enquanto o bloqueio vale.
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    $this->assertGuest();

    $this->travel(61)->seconds();

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($user);
});

test('login is blocked per ip when many emails are tried', function () {
    foreach (range(1, 20) as $attempt) {
        failLogin("pessoa{$attempt}@example.com", '10.0.0.2');
    }

    failLogin('outra@example.com', '10.0.0.2');
    expect(sessionError('email'))->toStartWith('Muitas tentativas.');

    failLogin('outra@example.com', '10.0.0.3')
        ->assertSessionHasErrors(['email' => 'E-mail ou senha não conferem. Confira e tente de novo.']);
});

test('registration is limited per ip', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.store'), ['email' => 'invalido']);
    }

    $this->post(route('register.store'), ['email' => 'invalido'])->assertSessionHasErrors('email');
    expect(sessionError('email'))->toStartWith('Muitas tentativas.');
});

test('password reset links are limited per email', function () {
    Notification::fake();
    $user = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.1.{$attempt}"])
            ->post(route('password.email'), ['email' => $user->email]);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.9'])
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors('email');
    expect(sessionError('email'))->toStartWith('Muitas tentativas.');
});

test('the throttled answer never echoes the password back', function () {
    foreach (range(1, 6) as $attempt) {
        failLogin('alguem@example.com');
    }

    expect(session()->getOldInput('password'))->toBeNull()
        ->and(session()->getOldInput('email'))->toBe('alguem@example.com');
});
