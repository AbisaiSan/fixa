<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccountPreferencesRequest;
use App\Http\Requests\Settings\StudyPreferencesRequest;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class PreferencesController extends Controller
{
    /**
     * Meta diária, idioma estudado e fuso da matrícula ativa (J1-B6, PLA-01, GAM-06).
     *
     * Trocar de idioma ativa a matrícula daquele idioma, criando se preciso,
     * sem apagar o progresso do idioma anterior.
     */
    public function updateStudy(StudyPreferencesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->enrollments()->where('target_language', '!=', $data['target_language'])->update(['active' => false]);

            /** @var Enrollment $enrollment */
            $enrollment = $user->enrollments()->firstOrNew(['target_language' => $data['target_language']]);
            $enrollment->fill($data)->forceFill(['active' => true])->save();
        });

        return back()->with('status', 'study-preferences-updated');
    }

    /**
     * Tema e notificações da conta. O tema também vai para o cookie lido no servidor.
     */
    public function updateAccount(AccountPreferencesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill($request->validated())->save();

        Cookie::queue('appearance', $user->theme, 60 * 24 * 365, httpOnly: false);

        return back()->with('status', 'account-preferences-updated');
    }
}
