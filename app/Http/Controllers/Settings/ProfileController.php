<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    /**
     * Atualiza nome e e-mail. E-mail novo volta a precisar de confirmação.
     * A exclusão completa da conta chega na jornada 6 (DAD-01).
     */
    public function update(ProfileUpdateRequest $request, AuditLog $audit): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            $audit->record('account.email_changed', $user);
        }

        return back()->with('status', 'profile-updated');
    }
}
