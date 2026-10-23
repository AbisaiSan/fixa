<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
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
     * Praticar: as atividades chegam nas J3 e J4. Com aula enviada, a tela
     * já não pede o primeiro envio.
     */
    public function practice(): Response
    {
        return Inertia::render('app/Practice', [
            'status' => 'empty',
            'hasLessons' => Lesson::query()->exists(),
        ]);
    }
}
