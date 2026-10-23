<?php

namespace App\Http\Controllers\Lessons;

use App\Domain\Lessons\CreateLesson;
use App\Domain\Lessons\DeleteLesson;
use App\Domain\Lessons\LessonSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lessons\StoreLessonRequest;
use App\Http\Requests\Lessons\UpdateLessonRequest;
use App\Jobs\ProcessLesson;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Lista de aulas (J2-F1): origem, data, status e resumo do que foi gerado.
     */
    public function index(): Response
    {
        $lessons = Lesson::query()
            ->with(['files:id,lesson_id,pages', 'review'])
            ->latest('lesson_date')
            ->latest('id')
            ->get();

        return Inertia::render('app/Lessons', [
            'status' => $lessons->isEmpty() ? 'empty' : 'ready',
            'lessons' => $lessons->map(fn (Lesson $lesson) => [
                'ulid' => $lesson->ulid,
                'title' => $lesson->title,
                'lesson_date' => $lesson->lesson_date->toDateString(),
                'source' => $lesson->source->value,
                'pages' => $lesson->source === LessonSource::Pdf ? (int) $lesson->files->sum('pages') : null,
                'status' => $lesson->status->value,
                'concepts' => $lesson->review === null ? null : count($lesson->review->concepts),
            ])->all(),
        ]);
    }

    public function store(StoreLessonRequest $request, CreateLesson $createLesson): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $source = LessonSource::from($request->string('source')->value());

        $files = match ($source) {
            LessonSource::Pdf => [$request->file('pdf')],
            LessonSource::Text => [],
        };

        $lesson = $createLesson->handle(
            user: $user,
            source: $source,
            title: $request->string('title')->value(),
            lessonDate: $request->string('lesson_date')->value(),
            text: $source === LessonSource::Text ? $request->string('text')->value() : null,
            files: $files,
        );

        ProcessLesson::dispatch($lesson->id)->afterCommit();

        return redirect()->route('lessons.show', $lesson);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('update', $lesson);

        $lesson->update($request->validated());

        return back()->with('status', 'lesson-updated');
    }

    public function destroy(Lesson $lesson, DeleteLesson $deleteLesson): RedirectResponse
    {
        Gate::authorize('delete', $lesson);

        /** @var User $user */
        $user = request()->user();
        $deleteLesson->handle($user, $lesson);

        return redirect()->route('lessons.index')->with('status', 'lesson-deleted');
    }

    public function show(Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);

        return Inertia::render('app/LessonShow', [
            'lesson' => [
                'ulid' => $lesson->ulid,
                'title' => $lesson->title,
                'lesson_date' => $lesson->lesson_date->toDateString(),
                'source' => $lesson->source->value,
                'status' => $lesson->status->value,
                'failure_reason' => $lesson->failure_reason,
                'word_count' => $lesson->word_count,
                'can_regenerate' => $lesson->canRegenerate(),
                'can_retry' => $lesson->canRetry(),
                'regenerations_left' => $lesson->regenerationsLeft(),
            ],
            'review' => $lesson->review === null ? null : [
                'ulid' => $lesson->review->ulid,
                'summary' => $lesson->review->summary,
                'concepts' => $lesson->review->concepts,
                'common_mistakes' => $lesson->review->common_mistakes,
                'can_do' => $lesson->review->can_do,
                'reported' => $lesson->review->reported,
                // Itens em análise (MAT-05): chave do item e o report, para "Desfazer".
                'open_reports' => $lesson->review->reports()->open()->get(['ulid', 'item_key'])
                    ->map(fn (ItemReport $report) => ['ulid' => $report->ulid, 'item' => $report->item_key])
                    ->all(),
            ],
        ]);
    }
}
