<?php

namespace App\Jobs;

use App\Domain\Ai\Exceptions\AiBudgetExceeded;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Cards\CardGenerator;
use App\Domain\Lessons\Extraction\TextExtractor;
use App\Domain\Lessons\LessonRejected;
use App\Domain\Lessons\LessonReviewer;
use App\Domain\Lessons\LessonStatus;
use App\Domain\Lessons\WordCounter;
use App\Models\Lesson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Throwable;

/**
 * Processa a aula na fila (J2-B11): antivírus e extração, depois a revisão
 * e os cartões pela IA. O envio responde na hora e a tela acompanha os estados.
 *
 * Falhas temporárias (IA sobrecarregada, antivírus fora) tentam de novo com
 * espera. Recusas e saída inválida da IA terminam a aula como "falhou",
 * guardando só o código do motivo (IAS-04).
 */
#[Tries(4)]
#[Backoff([20, 60, 180])]
#[Timeout(300)]
class ProcessLesson implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $lessonId) {}

    public function handle(TextExtractor $extractor, LessonReviewer $reviewer, CardGenerator $cards): void
    {
        $lesson = Lesson::withoutGlobalScopes()->find($this->lessonId);

        if ($lesson === null || ! $lesson->status->isProcessing()) {
            return;
        }

        try {
            if (in_array($lesson->status, [LessonStatus::Queued, LessonStatus::Extracting], true)) {
                $this->extract($lesson, $extractor);
            }

            if ($lesson->status === LessonStatus::Generating) {
                $reviewer->review($lesson);
                // Cartões na mesma tarefa, só na primeira geração (J3-B1).
                $cards->generate($lesson);
                $lesson->transitionTo(LessonStatus::Validating);
            }

            if ($lesson->status === LessonStatus::Validating) {
                $lesson->transitionTo(LessonStatus::Ready);
            }
        } catch (LessonRejected $exception) {
            $lesson->transitionTo(LessonStatus::Failed, $exception->reason);
        } catch (AiOutputInvalidException) {
            $lesson->transitionTo(LessonStatus::Failed, 'ai_invalid_output');
        } catch (AiBudgetExceeded) {
            $lesson->transitionTo(LessonStatus::Failed, 'ai_budget_reached');
        } catch (AiProviderException $exception) {
            if ($exception->retryable) {
                throw $exception;
            }

            $lesson->transitionTo(LessonStatus::Failed, 'ai_unavailable');
        }
    }

    /**
     * Esgotadas as tentativas, a aula aparece como "falhou" com "tentar de novo".
     */
    public function failed(?Throwable $exception): void
    {
        $lesson = Lesson::withoutGlobalScopes()->find($this->lessonId);

        if ($lesson !== null && $lesson->status->canTransitionTo(LessonStatus::Failed)) {
            $lesson->transitionTo(
                LessonStatus::Failed,
                $exception instanceof AiProviderException ? 'ai_unavailable' : 'processing_failed',
            );
        }
    }

    private function extract(Lesson $lesson, TextExtractor $extractor): void
    {
        if ($lesson->status === LessonStatus::Queued) {
            $lesson->transitionTo(LessonStatus::Extracting);
        }

        $text = $extractor->extract($lesson);
        $lesson->forceFill(['extracted_text' => $text, 'word_count' => WordCounter::count($text)])->save();
        $lesson->transitionTo(LessonStatus::Generating);
    }
}
