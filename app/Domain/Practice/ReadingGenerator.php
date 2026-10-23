<?php

namespace App\Domain\Practice;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Schemas\ReadingSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Models\Lesson;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gera o texto de leitura da aula (J4-B6, ATV-04, REV-03), uma vez por aula,
 * na mesma tarefa da revisão. As perguntas viram itens de escolha, corrigidos
 * pelo gabarito, sem IA.
 */
final class ReadingGenerator
{
    public function __construct(private StructuredGenerator $generator) {}

    public function generate(Lesson $lesson): void
    {
        if ($lesson->reading_generated_at !== null) {
            return;
        }

        $owner = User::query()->findOrFail($lesson->user_id);
        $level = $owner->activeEnrollment?->level;

        $response = $this->generator->generate(
            AiTask::Reading,
            new ReadingSchema,
            (string) $lesson->extracted_text,
            [
                'target_language' => (string) (config("fixa.language_names.{$lesson->language}") ?? $lesson->language),
                'level' => $level === null ? ActivityGenerator::DEFAULT_LEVEL : strtoupper($level),
            ],
            new AiCallContext(userId: $lesson->user_id, lessonId: $lesson->id),
        );

        $data = $response->data;

        DB::transaction(function () use ($lesson, $response, $data) {
            $session = (new PracticeSession)->forceFill([
                'user_id' => $lesson->user_id,
                'source_lesson_id' => $lesson->id,
                'kind' => SessionKind::Reading,
                'status' => SessionStatus::Ready,
                'content' => [
                    'title' => trim($data['title']),
                    'paragraphs' => array_map('trim', $data['paragraphs']),
                    'glossary' => $data['glossary'],
                ],
                'provider' => $response->provider,
                'model' => $response->model,
                'prompt_version' => (string) config('fixa.ai.prompts.reading', 'v1'),
            ]);
            $session->save();

            foreach (array_values($data['questions']) as $position => $question) {
                $options = array_map('trim', $question['options']);

                (new PracticeItem)->forceFill([
                    'user_id' => $lesson->user_id,
                    'practice_session_id' => $session->id,
                    'position' => $position + 1,
                    'type' => ItemType::Choice,
                    'content' => [
                        'sentence' => trim($question['question']),
                        'options' => $options,
                        'answer' => (int) $question['answer'],
                        'accepted' => [$options[(int) $question['answer']]],
                        'corrected' => $options[(int) $question['answer']],
                        'natural' => $options[(int) $question['answer']],
                        'why' => '',
                    ],
                ])->save();
            }

            $lesson->forceFill(['reading_generated_at' => now()])->save();
        });
    }
}
