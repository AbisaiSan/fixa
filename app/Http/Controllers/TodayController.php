<?php

namespace App\Http\Controllers;

use App\Domain\Cards\DailyQueue;
use App\Domain\Lessons\WordCounter;
use App\Domain\Plan\TodayPlan;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Domain\Practice\WeakPointBuilder;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hoje (J2-F6, J5-B9, PLA-06). Sem aula, convida a enviar a primeira; com
 * aula, mostra o plano do dia com o próximo bloco a 1 clique.
 */
class TodayController extends Controller
{
    public function show(Request $request, TodayPlan $plan): Response
    {
        /** @var User $user */
        $user = $request->user();
        $lesson = Lesson::query()->latest('created_at')->latest('id')->first();

        return Inertia::render('app/Today', [
            'status' => $lesson === null ? 'empty' : 'ready',
            // Adiado (J5-F7): a tela abre na hora, com esqueleto, e o plano chega
            // em seguida. Uma falha vira o estado de erro com "Tentar de novo".
            'plan' => Inertia::defer(fn () => $plan->for($user), rescue: true),
            'latestLesson' => $lesson === null ? null : [
                'ulid' => $lesson->ulid,
                'title' => $lesson->title,
                'lesson_date' => $lesson->lesson_date->toDateString(),
                'status' => $lesson->status->value,
            ],
        ]);
    }

    /**
     * Praticar (J4-F1): flashcards, escrita, leitura, ponto fraco, caderno
     * de erros e modo corrida, tudo a partir das aulas do aluno.
     */
    public function practice(Request $request, DailyQueue $queue): Response
    {
        /** @var User $user */
        $user = $request->user();
        $cards = $queue->for($user);

        $writing = $this->latestSession($user, SessionKind::Writing);
        $reading = $this->latestSession($user, SessionKind::Reading);
        $mistakes = Mistake::forUser($user)->open()->get();
        $due = $mistakes->filter(fn (Mistake $mistake) => $mistake->due_at !== null && $mistake->due_at->isPast())
            ->sortByDesc('priority');

        return Inertia::render('app/Practice', [
            'status' => Lesson::query()->exists() ? 'ready' : 'empty',
            'flash' => session('status'),
            'flashcards' => [
                'due' => $cards->whereNotNull('first_reviewed_at')->count(),
                'new' => $cards->whereNull('first_reviewed_at')->count(),
            ],
            'writing' => $writing === null ? null : [
                'ulid' => $writing->ulid,
                'lesson' => $writing->sourceLesson?->title,
                'items' => $writing->items()->count(),
                'completed' => $writing->status === SessionStatus::Completed,
            ],
            'reading' => $reading === null ? null : [
                'ulid' => $reading->ulid,
                'title' => $reading->content['title'] ?? '',
                'words' => WordCounter::count(implode(' ', $reading->content['paragraphs'] ?? [])),
                'completed' => $reading->status === SessionStatus::Completed,
            ],
            'weakPoint' => [
                'due' => min($due->count(), WeakPointBuilder::MAX_ITEMS),
                'point' => $due->first()?->content['point'],
            ],
            'mistakes' => [
                'open' => $mistakes->count(),
                'priority' => $mistakes->where('priority', true)->count(),
            ],
        ]);
    }

    /**
     * A sessão da aula mais recente ainda por fazer, ou a última feita.
     */
    private function latestSession(User $user, SessionKind $kind): ?PracticeSession
    {
        $sessions = PracticeSession::forUser($user)
            ->where('kind', $kind)
            ->with('sourceLesson:id,title,lesson_date')
            ->latest('id')
            ->limit(20)
            ->get();

        return $sessions->first(fn (PracticeSession $session) => $session->status !== SessionStatus::Completed)
            ?? $sessions->first();
    }
}
