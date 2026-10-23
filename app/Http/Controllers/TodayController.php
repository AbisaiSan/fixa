<?php

namespace App\Http\Controllers;

use App\Domain\Cards\DailyQueue;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hoje (J2-F6, fluxo A). Sem aula, convida a enviar a primeira; com aula,
 * leva à mais recente. O plano do dia chega na J5.
 */
class TodayController extends Controller
{
    public function show(): Response
    {
        $lesson = Lesson::query()->latest('created_at')->latest('id')->first();

        return Inertia::render('app/Today', [
            'status' => $lesson === null ? 'empty' : 'ready',
            'latestLesson' => $lesson === null ? null : [
                'ulid' => $lesson->ulid,
                'title' => $lesson->title,
                'lesson_date' => $lesson->lesson_date->toDateString(),
                'status' => $lesson->status->value,
            ],
        ]);
    }

    /**
     * Praticar: por ora, os flashcards (J3); escrita e leitura chegam na J4.
     */
    public function practice(Request $request, DailyQueue $queue): Response
    {
        /** @var User $user */
        $user = $request->user();
        $cards = $queue->for($user);

        return Inertia::render('app/Practice', [
            'status' => Lesson::query()->exists() ? 'ready' : 'empty',
            'flashcards' => [
                'due' => $cards->whereNotNull('first_reviewed_at')->count(),
                'new' => $cards->whereNull('first_reviewed_at')->count(),
            ],
        ]);
    }
}
