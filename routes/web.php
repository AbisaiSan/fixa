<?php

use App\Http\Controllers\Lessons\LessonController;
use App\Http\Controllers\Lessons\LessonFileController;
use Illuminate\Support\Facades\Route;

// Sem página pública de apresentação por enquanto: quem entra vai para Hoje.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'today' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // As abas ainda não têm conteúdo: o contrato de estados chega como "empty" até a J2.
    Route::inertia('hoje', 'app/Today', ['status' => 'empty'])->name('today');
    Route::inertia('aulas', 'app/Lessons', ['status' => 'empty'])->name('lessons.index');
    Route::inertia('aulas/enviar', 'app/LessonUpload')->name('lessons.create');
    Route::post('aulas', [LessonController::class, 'store'])->middleware('throttle:lesson-uploads')->name('lessons.store');
    Route::get('aulas/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::get('aulas/{lesson}/arquivos/{file}', [LessonFileController::class, 'show'])
        ->middleware('signed')
        ->scopeBindings()
        ->name('lessons.files.show');
    Route::inertia('praticar', 'app/Practice', ['status' => 'empty'])->name('practice');
    Route::inertia('vocabulario', 'app/Vocabulary', ['status' => 'empty'])->name('vocabulary.index');
    Route::inertia('progresso', 'app/Progress', ['status' => 'empty'])->name('progress');
});

require __DIR__.'/settings.php';
require __DIR__.'/legal.php';
