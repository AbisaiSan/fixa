<?php

use App\Http\Controllers\Lessons\ItemReportController;
use App\Http\Controllers\Lessons\LessonController;
use App\Http\Controllers\Lessons\LessonFileController;
use App\Http\Controllers\Lessons\LessonGenerationController;
use App\Http\Controllers\Lessons\LessonReviewController;
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
    Route::patch('aulas/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('aulas/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::post('aulas/{lesson}/regenerar', [LessonGenerationController::class, 'regenerate'])
        ->middleware('throttle:lesson-generations')
        ->name('lessons.regenerate');
    Route::post('aulas/{lesson}/tentar-de-novo', [LessonGenerationController::class, 'retry'])
        ->middleware('throttle:lesson-generations')
        ->name('lessons.retry');
    Route::patch('aulas/{lesson}/revisao', [LessonReviewController::class, 'update'])->name('lessons.review.update');
    Route::patch('aulas/{lesson}/revisao/conceitos/{concept}', [LessonReviewController::class, 'updateConcept'])
        ->where('concept', '[a-z0-9]+')
        ->name('lessons.review.concepts.update');
    Route::delete('aulas/{lesson}/revisao/conceitos/{concept}', [LessonReviewController::class, 'destroyConcept'])
        ->where('concept', '[a-z0-9]+')
        ->name('lessons.review.concepts.destroy');
    Route::post('aulas/{lesson}/reportes', [ItemReportController::class, 'store'])->name('lessons.reports.store');
    Route::delete('aulas/{lesson}/reportes/{report}', [ItemReportController::class, 'destroy'])
        ->scopeBindings()
        ->name('lessons.reports.destroy');
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
