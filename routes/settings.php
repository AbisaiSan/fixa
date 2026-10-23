<?php

use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('configuracoes')->group(function () {
    Route::get('/', [SettingsController::class, 'show'])->name('settings');

    Route::patch('perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('estudo', [PreferencesController::class, 'updateStudy'])->name('preferences.study');
    Route::patch('preferencias', [PreferencesController::class, 'updateAccount'])->name('preferences.account');

    Route::put('senha', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::delete('sessoes', [SessionController::class, 'destroyOthers'])
        ->middleware('throttle:6,1')
        ->name('sessions.destroy-others');
});
