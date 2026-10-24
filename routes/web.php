<?php

use App\Domain\Lessons\Extraction\TextExtractor;
use App\Http\Controllers\Cards\CardController;
use App\Http\Controllers\Cards\CardReportController;
use App\Http\Controllers\Cards\CardReviewController;
use App\Http\Controllers\Cards\FlashcardSessionController;
use App\Http\Controllers\Lessons\ItemReportController;
use App\Http\Controllers\Lessons\LessonController;
use App\Http\Controllers\Lessons\LessonFileController;
use App\Http\Controllers\Lessons\LessonGenerationController;
use App\Http\Controllers\Lessons\LessonReviewController;
use App\Http\Controllers\Plan\ReminderUnsubscribeController;
use App\Http\Controllers\Plan\RushController;
use App\Http\Controllers\Practice\AttemptController;
use App\Http\Controllers\Practice\MistakeController;
use App\Http\Controllers\Practice\PracticeItemReportController;
use App\Http\Controllers\Practice\PracticeSessionController;
use App\Http\Controllers\Practice\ReadingController;
use App\Http\Controllers\Practice\ReadingWordController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\Vocabulary\WordController;
use App\Http\Requests\Lessons\StoreLessonRequest;
use Illuminate\Support\Facades\Route;

// Sem página pública de apresentação por enquanto: quem entra vai para Hoje.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'today' : 'login'))->name('home');

// Cancelar o lembrete diário pelo link assinado do e-mail, sem login (J5-B10).
Route::get('lembrete/cancelar/{user}', [ReminderUnsubscribeController::class, 'show'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('reminders.unsubscribe');
Route::post('lembrete/cancelar/{user}', [ReminderUnsubscribeController::class, 'destroy'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('reminders.unsubscribe.confirm');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('hoje', [TodayController::class, 'show'])->name('today');
    Route::get('aulas', [LessonController::class, 'index'])->name('lessons.index');
    Route::inertia('aulas/enviar', 'app/LessonUpload', ['limits' => [
        'max_megabytes' => StoreLessonRequest::MAX_FILE_KILOBYTES / 1024,
        'max_pages' => StoreLessonRequest::MAX_PAGES,
        'min_words' => TextExtractor::MIN_WORDS,
    ], 'upcomingLanguages' => config('fixa.upcoming_languages')])->name('lessons.create');
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
    Route::put('aulas/{lesson}/revisao/consigo', [LessonReviewController::class, 'updateCanDo'])->name('lessons.review.can-do');
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
    Route::get('praticar', [TodayController::class, 'practice'])->name('practice');
    Route::get('praticar/flashcards', [FlashcardSessionController::class, 'show'])->name('flashcards');
    Route::post('praticar/corrida', [RushController::class, 'store'])->middleware('throttle:card-reviews')->name('rush.store');
    Route::post('praticar/flashcards/{card}/respostas', [CardReviewController::class, 'store'])
        ->middleware('throttle:card-reviews')
        ->name('cards.reviews.store');
    Route::get('praticar/escrita/{session}', [PracticeSessionController::class, 'writing'])->name('practice.writing');
    Route::get('praticar/leitura/{session}', [ReadingController::class, 'show'])->name('practice.reading');
    Route::get('praticar/caderno-de-erros', [MistakeController::class, 'index'])->name('practice.mistakes');
    Route::get('praticar/ponto-fraco', [PracticeSessionController::class, 'weakPoint'])->name('practice.weak-point');
    Route::post('praticar/itens/{item}/reportes', [PracticeItemReportController::class, 'store'])->name('practice.items.reports.store');
    Route::delete('praticar/itens/{item}/reportes/{report}', [PracticeItemReportController::class, 'destroy'])->name('practice.items.reports.destroy');
    Route::post('praticar/itens/{item}/respostas', [AttemptController::class, 'store'])
        ->middleware('throttle:practice-answers')
        ->name('practice.attempts.store');
    Route::post('praticar/leitura/{session}/palavras', [ReadingWordController::class, 'store'])
        ->middleware('throttle:word-additions')
        ->name('practice.reading.words.store');
    Route::post('cartoes', [CardController::class, 'store'])->name('cards.store');
    Route::patch('cartoes/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('cartoes/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::post('cartoes/{card}/suspender', [CardController::class, 'suspend'])->name('cards.suspend');
    Route::delete('cartoes/{card}/suspender', [CardController::class, 'resume'])->name('cards.resume');
    Route::post('cartoes/{card}/reportes', [CardReportController::class, 'store'])->name('cards.reports.store');
    Route::delete('cartoes/{card}/reportes/{report}', [CardReportController::class, 'destroy'])->name('cards.reports.destroy');
    Route::get('vocabulario', [WordController::class, 'index'])->name('vocabulary.index');
    Route::get('vocabulario/{word}', [WordController::class, 'show'])->name('words.show');
    Route::patch('vocabulario/{word}', [WordController::class, 'update'])->name('words.update');
    Route::post('vocabulario/{word}/reportes', [WordController::class, 'report'])->name('words.reports.store');
    Route::delete('vocabulario/{word}/reportes/{report}', [WordController::class, 'withdrawReport'])->name('words.reports.destroy');
    Route::post('vocabulario', [WordController::class, 'store'])->middleware('throttle:word-additions')->name('words.store');
    Route::inertia('progresso', 'app/Progress', ['status' => 'empty'])->name('progress');
});

require __DIR__.'/settings.php';
require __DIR__.'/legal.php';
