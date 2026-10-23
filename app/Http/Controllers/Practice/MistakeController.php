<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Practice\ErrorCategory;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Mistake;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Caderno de erros (J4-F6, ERR-01 a ERR-03): os pontos abertos por
 * categoria, com repetições, próxima revisão e acertos seguidos.
 */
class MistakeController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $category = ErrorCategory::tryFrom($request->string('categoria')->value());

        $open = Mistake::forUser($user)->open()->with('sourceLesson:id,lesson_date')->get();
        $counts = ['all' => $open->count()];
        foreach (ErrorCategory::cases() as $case) {
            $counts[$case->value] = $open->where('category', $case)->count();
        }

        $mistakes = $open
            ->when($category, fn ($items) => $items->where('category', $category))
            ->sortBy([['priority', 'desc'], ['due_at', 'asc']])
            ->values();

        return Inertia::render('app/Mistakes', [
            'filter' => $category?->value,
            'counts' => $counts,
            'mistakes' => $mistakes->map(fn (Mistake $mistake) => [
                'ulid' => $mistake->ulid,
                'category' => $mistake->category->value,
                'priority' => $mistake->priority,
                'repetitions' => $mistake->repetitions,
                'answer' => $mistake->content['answer'],
                'corrected' => $mistake->content['corrected'],
                'explanation' => $mistake->content['explanation'],
                'step' => $mistake->step,
                'correct_streak' => $mistake->correct_streak,
                // Dias até voltar, no dia do aluno: 0 é hoje (ou atrasado).
                'due_in_days' => $mistake->due_at === null ? null
                    : max(0, (int) $today->diffInDays(CarbonImmutable::instance($mistake->due_at)->setTimezone($timezone)->startOfDay(), false)),
                'lesson_date' => $mistake->sourceLesson?->lesson_date->toDateString(),
            ])->all(),
        ]);
    }
}
