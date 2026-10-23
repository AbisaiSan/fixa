<?php

namespace App\Domain\Lessons;

use App\Jobs\ProcessLesson;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nova geração, sempre a pedido do aluno e com limite por aula (REV-04).
 * Nada no sistema regenera a revisão sozinho.
 */
final class RequestGeneration
{
    /**
     * "Regenerar": gera de novo a partir do material já extraído.
     *
     * @throws ValidationException
     */
    public function regenerate(Lesson $lesson): void
    {
        if (! $lesson->canRegenerate()) {
            throw ValidationException::withMessages([
                'lesson' => $lesson->regenerationsLeft() === 0
                    ? 'Você já usou as novas gerações desta aula. Edite os itens que quiser mudar.'
                    : 'Esta aula ainda não está pronta para gerar de novo.',
            ]);
        }

        $this->start($lesson, LessonStatus::Generating);
    }

    /**
     * "Tentar de novo" depois de uma falha que outra tentativa pode resolver.
     *
     * @throws ValidationException
     */
    public function retry(Lesson $lesson): void
    {
        if (! $lesson->canRetry()) {
            throw ValidationException::withMessages([
                'lesson' => $lesson->status === LessonStatus::Failed && $lesson->regenerationsLeft() === 0
                    ? 'Você já usou as novas tentativas desta aula.'
                    : 'Esta aula não pode ser processada de novo. Envie o material outra vez.',
            ]);
        }

        $this->start($lesson, LessonStatus::Queued);
    }

    private function start(Lesson $lesson, LessonStatus $status): void
    {
        DB::transaction(function () use ($lesson, $status) {
            $lesson->transitionTo($status);
            $lesson->increment('regenerations');
        });

        ProcessLesson::dispatch($lesson->id)->afterCommit();
    }
}
