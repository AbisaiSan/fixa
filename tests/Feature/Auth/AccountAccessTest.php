<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('auth pages use the portuguese paths from the prototype', function () {
    expect(route('login', absolute: false))->toBe('/entrar')
        ->and(route('register', absolute: false))->toBe('/cadastro')
        ->and(route('password.request', absolute: false))->toBe('/recuperar')
        ->and(route('verification.notice', absolute: false))->toBe('/verificar')
        ->and(route('two-factor.login', absolute: false))->toBe('/2fa');
});

test('unverified students are sent to the verification page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect('/verificar');
});

test('registration sends the verification email in portuguese', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Confirme seu e-mail' && $mail->actionText === 'Confirmar e-mail';
    });
});

describe('password reset link', function () {
    beforeEach(function () {
        Notification::fake();

        $this->user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $this->user->email]);

        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) {
            $this->token = $notification->token;
            $this->mail = $notification->toMail($this->user);

            return true;
        });
    });

    $reset = fn (string $token, string $password = 'nova-senha-segura-123') => [
        'token' => $token,
        'email' => test()->user->email,
        'password' => $password,
        'password_confirmation' => $password,
    ];

    test('is sent in portuguese and states the short validity', function () {
        expect($this->mail->subject)->toBe('Redefina sua senha')
            ->and(implode(' ', [...$this->mail->introLines, ...$this->mail->outroLines]))
            ->toContain('vale por 30 minutos e só pode ser usado uma vez');
    });

    test('can be used only once', function () use ($reset) {
        $this->post(route('password.update'), $reset($this->token))->assertSessionHasNoErrors();

        $this->post(route('password.update'), $reset($this->token, 'outra-senha-segura-456'))
            ->assertSessionHasErrors(['email' => 'Este link de redefinição não vale mais. Peça um novo.']);
    });

    test('expires after 30 minutes', function () use ($reset) {
        $this->travel(31)->minutes();

        $this->post(route('password.update'), $reset($this->token))->assertSessionHasErrors('email');
    });

    test('does not reveal whether the email exists', function () {
        $this->post(route('password.email'), ['email' => 'ninguem@example.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se houver uma conta com este e-mail, enviamos um link para redefinir a senha.');
    });
});
