<?php

namespace App\Domain\Cards;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Schemas\CardsSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Domain\Vocabulary\WordBook;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Gera os cartões da aula na mesma tarefa da revisão (J3-B1, FLA-01, FLA-02).
 * Só uma vez por aula: regenerar a revisão não recria cartões, que guardam
 * o histórico de revisões do aluno.
 *
 * Cartão que o aluno já tem (mesma frente e mesmo significado, FLA-05) não é
 * recriado: só ganha a referência à nova aula.
 */
final class CardGenerator
{
    public function __construct(private StructuredGenerator $generator, private WordBook $words) {}

    public function generate(Lesson $lesson): void
    {
        if ($lesson->cards_generated_at !== null) {
            return;
        }

        $max = (int) config('fixa.ai.max_cards_per_lesson', 30);

        $response = $this->generator->generate(
            AiTask::Cards,
            new CardsSchema($max),
            (string) $lesson->extracted_text,
            [
                'target_language' => (string) (config("fixa.language_names.{$lesson->language}") ?? $lesson->language),
                'max_cards' => (string) $max,
            ],
            new AiCallContext(userId: $lesson->user_id, lessonId: $lesson->id),
        );

        $owner = User::query()->findOrFail($lesson->user_id);

        $generation = [
            'provider' => $response->provider,
            'model' => $response->model,
            'prompt_version' => (string) config('fixa.ai.prompts.cards', 'v1'),
        ];

        DB::transaction(function () use ($lesson, $owner, $response, $max, $generation) {
            foreach (array_slice($response->data['cards'], 0, $max) as $item) {
                // Palavras entram também no vocabulário (VOC-01).
                $word = isset($item['word']) && $item['kind'] === CardKind::Word->value
                    ? $this->words->fromLesson($owner, $lesson, $item['front'], $item['back'], $item['word'], $generation)
                    : null;

                $existing = Card::forUser($owner)
                    ->where('dedupe_key', CardIdentity::key($item['front'], $item['back']))
                    ->first();

                if ($existing !== null) {
                    $existing->lessons()->syncWithoutDetaching([$lesson->id]);

                    if ($word !== null && $existing->word_id === null) {
                        $existing->forceFill(['word_id' => $word->id])->save();
                    }

                    continue;
                }

                $card = (new Card)->forceFill([
                    'user_id' => $lesson->user_id,
                    'source_lesson_id' => $lesson->id,
                    'kind' => CardKind::from($item['kind']),
                    'front' => trim($item['front']),
                    'back' => trim($item['back']),
                    'example' => trim($item['example']),
                    'example_translation' => trim($item['example_translation']),
                    'word_id' => $word?->id,
                    ...$generation,
                ]);
                $card->save();
                $card->lessons()->attach($lesson->id);
            }

            $lesson->forceFill(['cards_generated_at' => now()])->save();
        });
    }
}
