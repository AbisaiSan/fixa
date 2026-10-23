<?php

namespace App\Http\Controllers\Lessons;

use App\Domain\Lessons\RequestGeneration;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * "Regenerar" e "Tentar de novo", só por pedido do aluno (REV-04).
 */
class LessonGenerationController extends Controller
{
    public function regenerate(Lesson $lesson, RequestGeneration $generation): RedirectResponse
    {
        Gate::authorize('update', $lesson);

        $generation->regenerate($lesson);

        return redirect()->route('lessons.show', $lesson);
    }

    public function retry(Lesson $lesson, RequestGeneration $generation): RedirectResponse
    {
        Gate::authorize('update', $lesson);

        $generation->retry($lesson);

        return redirect()->route('lessons.show', $lesson);
    }
}
