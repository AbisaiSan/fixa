<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SettingsController extends Controller
{
    /**
     * Configurações em uma página, como no protótipo (J1-F3): estudo,
     * notificações e segurança, tema e conta.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $enrollment = $user->activeEnrollment;

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();
        }

        return Inertia::render('settings/Index', [
            'study' => [
                'daily_goal_minutes' => $enrollment->daily_goal_minutes ?? Enrollment::DEFAULT_DAILY_GOAL,
                'target_language' => $enrollment->target_language ?? config('fixa.target_language'),
                'timezone' => $enrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE,
                'level' => $enrollment?->level,
            ],
            'goalRange' => ['min' => Enrollment::MIN_DAILY_GOAL, 'max' => Enrollment::MAX_DAILY_GOAL, 'step' => 5],
            'languages' => [
                'available' => config('fixa.available_languages'),
                'upcoming' => config('fixa.upcoming_languages'),
            ],
            'timezones' => config('fixa.timezones'),
            'preferences' => [
                'theme' => $user->theme,
                'notify_daily_reminder' => $user->notify_daily_reminder,
                'notify_weekly_summary' => $user->notify_weekly_summary,
                'notify_streak' => $user->notify_streak,
            ],
            'twoFactor' => [
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
                'required' => $user->isAdmin(),
            ],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'status' => $request->session()->get('status'),
        ]);
    }
}
