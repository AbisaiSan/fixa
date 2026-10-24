<?php

namespace App\Http\Controllers\Plan;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Cancelar o lembrete diário pelo link do e-mail (J5-B10), sem entrar na
 * conta. O link é assinado; abrir só mostra a confirmação, para um leitor
 * de e-mail que abre links sozinho não desligar o lembrete.
 */
class ReminderUnsubscribeController extends Controller
{
    public function show(Request $request, User $user): View
    {
        return view('reminders.unsubscribe', ['done' => false, 'action' => $request->fullUrl()]);
    }

    public function destroy(User $user, AuditLog $audit): View
    {
        if ($user->notify_daily_reminder) {
            $user->forceFill(['notify_daily_reminder' => false])->save();
            $audit->record('reminder.unsubscribed', $user, ['via' => 'email']);
        }

        return view('reminders.unsubscribe', ['done' => true, 'action' => null]);
    }
}
