<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Support\Audit\AuditLog;
use Illuminate\Http\RedirectResponse;

class SecurityController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request, AuditLog $audit): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        $audit->record('auth.password_changed', $request->user());

        return back()->with('status', 'password-updated');
    }
}
