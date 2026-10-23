<?php

namespace App\Http\Controllers\Lessons;

use App\Domain\Lessons\CreateLesson;
use App\Domain\Lessons\LessonSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lessons\StoreLessonRequest;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
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

        return redirect()->route('lessons.show', $lesson);
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
            ],
        ]);
    }
}
