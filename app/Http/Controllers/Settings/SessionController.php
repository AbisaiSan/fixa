<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    /**
     * Encerra a sessão em todos os outros aparelhos (ACE-01).
     *
     * Apaga as outras sessões do aluno e troca o token de "lembrar de mim",
     * para que nenhum cookie antigo volte a entrar.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return back()->with('status', 'other-sessions-ended');
    }
}
