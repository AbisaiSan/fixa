<?php

use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

Route::get('termos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('privacidade', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::middleware('auth')->group(function () {
    Route::get('termos/aceitar', [LegalController::class, 'edit'])->name('legal.accept');
    Route::post('termos/aceitar', [LegalController::class, 'store'])->name('legal.accept.store');
});
