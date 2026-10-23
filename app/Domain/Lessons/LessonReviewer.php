<?php

namespace App\Domain\Lessons;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Schemas\LessonReviewSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Models\Lesson;
use App\Models\LessonReview;

/**
 * Gera e guarda a revisão da aula (J2-B9, REV-01 a REV-04). Só do material
 * da própria aula, sem dados pessoais do aluno no pedido.
 */
final class LessonReviewer
{
    public function __construct(private StructuredGenerator $generator) {}

    public function review(Lesson $lesson): LessonReview
    {
        $response = $this->generator->generate(
            AiTask::LessonReview,
            new LessonReviewSchema,
            (string) $lesson->extracted_text,
            ['target_language' => (string) (config("fixa.language_names.{$lesson->language}") ?? $lesson->language)],
            new AiCallContext(userId: $lesson->user_id, lessonId: $lesson->id),
        );

        $review = LessonReview::withoutGlobalScopes()->where('lesson_id', $lesson->id)->first() ?? new LessonReview;
        $review->forceFill([
            'user_id' => $lesson->user_id,
            'lesson_id' => $lesson->id,
            'summary' => $response->data['summary'],
            'concepts' => $response->data['concepts'],
            'common_mistakes' => $response->data['common_mistakes'],
            'can_do' => $response->data['can_do'],
            'provider' => $response->provider,
            'model' => $response->model,
            'prompt_version' => config('fixa.ai.prompts.lesson_review', 'v1'),
            'reported' => false,
        ])->save();

        return $review;
    }
}
