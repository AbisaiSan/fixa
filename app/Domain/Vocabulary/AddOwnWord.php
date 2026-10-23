<?php

namespace App\Domain\Vocabulary;

use App\Domain\Ai\AiCallContext;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\DailyAiAllowance;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Schemas\WordDetailsSchema;
use App\Domain\Ai\StructuredGenerator;
use App\Domain\Cards\CardIdentity;
use App\Domain\Cards\CardKind;
use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Palavra salva pelo aluno fora da aula (J3-B8, VOC-03), com origem
 * "adicionada por mim". Pode ser completada pela IA dentro do limite diário
 * (ATV-05). O que o aluno escreveu sempre vale mais que a sugestão da IA.
 * Ganha um cartão, para entrar na prática.
 */
final class AddOwnWord
{
    public function __construct(
        private StructuredGenerator $generator,
        private DailyAiAllowance $allowance,
    ) {}

    /**
     * @param  array{term: string, translation?: string|null, part_of_speech?: string|null, definition?: string|null}  $input
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $input, bool $fillWithAi): Word
    {
        $language = $user->activeEnrollment->target_language ?? (string) config('fixa.target_language');
        $term = trim($input['term']);
        $suggestion = null;
        $aiProblem = null;

        if ($fillWithAi) {
            [$suggestion, $aiProblem] = $this->suggest($user, $term, $language);
        }

        $given = fn (string $field) => isset($input[$field]) && trim((string) $input[$field]) !== '' ? trim((string) $input[$field]) : null;
        $translation = $given('translation') ?? $suggestion['translation'] ?? null;

        if ($translation === null) {
            throw ValidationException::withMessages([
                'translation' => match ($aiProblem) {
                    'limit' => 'Você chegou ao limite de pedidos à IA de hoje. Escreva a tradução para salvar.',
                    'failed' => 'Não conseguimos completar com IA agora. Escreva a tradução para salvar.',
                    default => 'Escreva a tradução.',
                },
            ]);
        }

        if (Word::forUser($user)->where('dedupe_key', CardIdentity::key($term, $translation))->exists()) {
            throw ValidationException::withMessages(['term' => 'Esta palavra já está no seu vocabulário.']);
        }

        return DB::transaction(function () use ($user, $language, $term, $translation, $given, $suggestion) {
            $word = (new Word)->forceFill([
                'user_id' => $user->id,
                'source_lesson_id' => null,
                'origin' => WordOrigin::Mine,
                'language' => $language,
                'term' => $term,
                'translation' => $translation,
                'part_of_speech' => $given('part_of_speech') ?? $suggestion['part_of_speech'] ?? null,
                'definition' => $given('definition') ?? $suggestion['definition'] ?? null,
                'examples' => $suggestion['examples'] ?? null,
                'collocations' => $suggestion['collocations'] ?? null,
                'provider' => $suggestion['provider'] ?? null,
                'model' => $suggestion['model'] ?? null,
                'prompt_version' => $suggestion === null ? null : (string) config('fixa.ai.prompts.word_details', 'v1'),
            ]);
            $word->save();

            $card = Card::forUser($user)->where('dedupe_key', CardIdentity::key($term, $translation))->first();

            if ($card !== null) {
                $card->forceFill(['word_id' => $card->word_id ?? $word->id])->save();
            } else {
                (new Card)->forceFill([
                    'user_id' => $user->id,
                    'kind' => CardKind::Word,
                    'front' => $term,
                    'back' => $translation,
                    'example' => $suggestion['examples'][0]['target'] ?? null,
                    'example_translation' => $suggestion['examples'][0]['translation'] ?? null,
                    'word_id' => $word->id,
                ])->save();
            }

            return $word;
        });
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: 'limit'|'failed'|null}
     */
    private function suggest(User $user, string $term, string $language): array
    {
        if ($this->allowance->left($user) === 0) {
            return [null, 'limit'];
        }

        try {
            $response = $this->generator->generate(
                AiTask::WordDetails,
                new WordDetailsSchema,
                $term,
                ['target_language' => (string) (config("fixa.language_names.{$language}") ?? $language)],
                new AiCallContext(userId: $user->id),
            );
        } catch (AiOutputInvalidException|AiProviderException) {
            // Inclui o teto mensal (AiBudgetExceeded).
            return [null, 'failed'];
        }

        return [[...$response->data, 'provider' => $response->provider, 'model' => $response->model], null];
    }
}
