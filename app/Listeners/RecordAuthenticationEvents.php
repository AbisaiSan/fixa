<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Leva os eventos de autenticação para a trilha de auditoria (J1-B8, OPE-03).
 */
class RecordAuthenticationEvents
{
    public function __construct(private AuditLog $audit) {}

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'registered',
            Verified::class => 'verified',
            Login::class => 'login',
            Logout::class => 'logout',
            Failed::class => 'failed',
            Lockout::class => 'lockout',
            PasswordReset::class => 'passwordReset',
            TwoFactorAuthenticationEnabled::class => 'twoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'twoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'twoFactorDisabled',
            TwoFactorAuthenticationFailed::class => 'twoFactorFailed',
            RecoveryCodesGenerated::class => 'recoveryCodesGenerated',
            RecoveryCodeReplaced::class => 'recoveryCodeUsed',
        ];
    }

    public function registered(Registered $event): void
    {
        $this->audit->record('auth.registered', $this->user($event->user));
    }

    public function verified(Verified $event): void
    {
        $this->audit->record('auth.email_verified', $this->user($event->user));
    }

    public function login(Login $event): void
    {
        $this->audit->record('auth.login', $this->user($event->user), ['remember' => $event->remember]);
    }

    public function logout(Logout $event): void
    {
        $this->audit->record('auth.logout', $this->user($event->user));
    }

    public function failed(Failed $event): void
    {
        $this->audit->record('auth.login_failed', $this->user($event->user), [
            'account' => AuditLog::emailFingerprint($event->credentials['email'] ?? null),
        ]);
    }

    public function lockout(Lockout $event): void
    {
        $this->audit->record('auth.lockout', null, [
            'account' => AuditLog::emailFingerprint($event->request->input('email')),
            'route' => $event->request->route()?->getName(),
        ]);
    }

    public function passwordReset(PasswordReset $event): void
    {
        $this->audit->record('auth.password_reset', $this->user($event->user));
    }

    public function twoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->audit->record('auth.two_factor_enabled', $this->user($event->user));
    }

    public function twoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->audit->record('auth.two_factor_confirmed', $this->user($event->user));
    }

    public function twoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->audit->record('auth.two_factor_disabled', $this->user($event->user));
    }

    public function twoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->audit->record('auth.two_factor_failed', $this->user($event->user));
    }

    public function recoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->audit->record('auth.recovery_codes_generated', $this->user($event->user));
    }

    public function recoveryCodeUsed(RecoveryCodeReplaced $event): void
    {
        $this->audit->record('auth.recovery_code_used', $this->user($event->user));
    }

    private function user(mixed $user): ?User
    {
        return $user instanceof User ? $user : null;
    }
}
