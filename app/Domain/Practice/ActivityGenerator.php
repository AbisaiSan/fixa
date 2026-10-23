<?php

namespace App\Domain\Practice;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Schemas\ActivitiesSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Models\Lesson;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gera a sessão de escrita da aula na mesma tarefa da revisão (J4-B1,
 * ATV-01), uma vez por aula e no nível do aluno (REV-03). Os itens já vêm
 * com gabarito, versão corrigida e explicação, para que a maior parte da
 * correção não precise de IA (ATV-02).
 */
final class ActivityGenerator
{
    public const DEFAULT_LEVEL = 'intermediário';

    public function __construct(private StructuredGenerator $generator) {}

    public function generate(Lesson $lesson): void
    {
        if ($lesson->activities_generated_at !== null) {
            return;
        }

        $owner = User::query()->findOrFail($lesson->user_id);
        $level = $owner->activeEnrollment?->level;

        $response = $this->generator->generate(
            AiTask::Activities,
            new ActivitiesSchema,
            (string) $lesson->extracted_text,
            [
                'target_language' => (string) (config("fixa.language_names.{$lesson->language}") ?? $lesson->language),
                'level' => $level === null ? self::DEFAULT_LEVEL : strtoupper($level),
            ],
            new AiCallContext(userId: $lesson->user_id, lessonId: $lesson->id),
        );

        $items = $response->data['items'];

        DB::transaction(function () use ($lesson, $response, $items) {
            $session = (new PracticeSession)->forceFill([
                'user_id' => $lesson->user_id,
                'source_lesson_id' => $lesson->id,
                'kind' => SessionKind::Writing,
                'status' => SessionStatus::Ready,
                'provider' => $response->provider,
                'model' => $response->model,
                'prompt_version' => (string) config('fixa.ai.prompts.activities', 'v1'),
            ]);
            $session->save();

            foreach (array_values($items) as $position => $item) {
                (new PracticeItem)->forceFill([
                    'user_id' => $lesson->user_id,
                    'practice_session_id' => $session->id,
                    'position' => $position + 1,
                    'type' => ItemType::from($item['type']),
                    'context' => ItemContext::from($item['context']),
                    'content' => [
                        'instruction' => trim($item['instruction']),
                        'sentence' => trim($item['sentence']),
                        'accepted' => array_values(array_filter(array_map('trim', $item['accepted']), fn (string $answer) => $answer !== '')),
                        'corrected' => trim($item['corrected']),
                        'natural' => trim($item['natural']),
                        'why' => trim($item['why']),
                        'point' => trim($item['point']),
                    ],
                ])->save();
            }

            $lesson->forceFill(['activities_generated_at' => now()])->save();
        });
    }
}
