<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\PasswordResetLinkFailedResponse;
use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkFailedResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Limites de tentativas por e-mail e por IP (ACE-05).
     * Ao estourar, o formulário volta com uma mensagem em vez de uma página 429.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login|'.$this->emailKey($request).'|'.$request->ip())->response($this->tooMany('email')),
            Limit::perMinute(20)->by('login-ip|'.$request->ip())->response($this->tooMany('email')),
        ]);

        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by('2fa|'.$request->session()->get('login.id'))->response($this->tooMany('code')),
            Limit::perMinute(20)->by('2fa-ip|'.$request->ip())->response($this->tooMany('code')),
        ]);

        RateLimiter::for('auth-forms', fn (Request $request) => match ($request->route()?->getName()) {
            'register.store' => [
                Limit::perMinute(5)->by('register|'.$request->ip())->response($this->tooMany('email')),
                Limit::perHour(20)->by('register-hour|'.$request->ip())->response($this->tooMany('email')),
            ],
            'password.email' => [
                Limit::perMinutes(15, 3)->by('reset-link|'.$this->emailKey($request))->response($this->tooMany('email')),
                Limit::perHour(10)->by('reset-link-ip|'.$request->ip())->response($this->tooMany('email')),
            ],
            'password.update' => [
                Limit::perMinute(10)->by('reset|'.$request->ip())->response($this->tooMany('email')),
            ],
            default => Limit::none(),
        });
    }

    private function emailKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input(Fortify::username())));
    }

    /**
     * @return Closure(Request, array<string, mixed>): RedirectResponse
     */
    private function tooMany(string $field): Closure
    {
        return function (Request $request, array $headers) use ($field) {
            // O middleware throttle não dispara o evento; a auditoria precisa dele (OPE-03).
            event(new Lockout($request));

            return back()
                ->withInput($request->except('password', 'password_confirmation', 'code', 'recovery_code'))
                ->withErrors([$field => __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60])]);
        };
    }
}
