<?php

namespace App\Http\Controllers\Lessons;

use App\Domain\Lessons\EditLessonReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lessons\UpdateReviewItemRequest;
use App\Models\Lesson;
use App\Models\LessonReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Editar e remover itens da revisão (MAT-05).
 */
class LessonReviewController extends Controller
{
    public function update(UpdateReviewItemRequest $request, Lesson $lesson, EditLessonReview $edit): RedirectResponse
    {
        $review = $this->review($lesson);

        if ($request->has('summary')) {
            $edit->updateSummary($review, $request->string('summary')->value());
        }

        return back()->with('status', 'review-updated');
    }

    public function updateConcept(UpdateReviewItemRequest $request, Lesson $lesson, string $concept, EditLessonReview $edit): RedirectResponse
    {
        $review = $this->review($lesson, $concept);

        if ($request->has('explanation')) {
            $edit->updateConcept($review, $concept, $request->string('explanation')->value());
        }

        return back()->with('status', 'review-updated');
    }

    public function destroyConcept(Lesson $lesson, string $concept, EditLessonReview $edit): RedirectResponse
    {
        $edit->removeConcept($this->review($lesson, $concept), $concept);

        return back()->with('status', 'concept-removed');
    }

    private function review(Lesson $lesson, ?string $concept = null): LessonReview
    {
        Gate::authorize('update', $lesson);

        $review = $lesson->review;

        abort_if($review === null || ($concept !== null && ! $review->hasConcept($concept)), 404);

        return $review;
    }
}
