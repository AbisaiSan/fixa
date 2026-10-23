<?php

namespace App\Domain\Vocabulary;

use App\Domain\Cards\CardIdentity;
use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Edição da palavra pelo aluno (J3-F5). A tradução também é o verso do
 * cartão da palavra, que acompanha a mudança.
 */
final class EditWord
{
    /**
     * @param  array{translation: string, part_of_speech?: string|null, definition?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, Word $word, array $data): void
    {
        $translation = trim($data['translation']);
        $key = CardIdentity::key($word->term, $translation);

        if (Word::forUser($user)->where('dedupe_key', $key)->whereKeyNot($word->id)->exists()) {
            throw ValidationException::withMessages(['translation' => 'Você já tem esta palavra com este significado.']);
        }

        $card = Card::forUser($user)->where('word_id', $word->id)->first();

        if ($card !== null && Card::forUser($user)->where('dedupe_key', CardIdentity::key($card->front, $translation))->whereKeyNot($card->id)->exists()) {
            throw ValidationException::withMessages(['translation' => 'Você já tem um cartão com este significado.']);
        }

        $optional = fn (?string $value) => $value === null || trim($value) === '' ? null : trim($value);

        DB::transaction(function () use ($word, $card, $translation, $data, $optional) {
            $word->forceFill([
                'translation' => $translation,
                'part_of_speech' => $optional($data['part_of_speech'] ?? null),
                'definition' => $optional($data['definition'] ?? null),
            ])->save();

            $card?->forceFill(['back' => $translation])->save();
        });
    }
}
