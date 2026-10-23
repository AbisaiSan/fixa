<?php

namespace App\Domain\Cards;

use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Criar, editar, suspender e remover cartão (J3-B6, FLA-06). Cartão criado
 * pelo aluno não tem provedor nem versão de prompt, e a aula é opcional.
 */
final class ManageCard
{
    /**
     * @param  array{front: string, back: string, example?: string|null, example_translation?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function create(User $user, array $data, ?Lesson $lesson = null): Card
    {
        $this->ensureUnique($user, $data['front'], $data['back']);

        return DB::transaction(function () use ($user, $data, $lesson) {
            $card = (new Card)->forceFill([
                ...$this->content($data),
                'user_id' => $user->id,
                'source_lesson_id' => $lesson?->id,
                'kind' => $this->kindOf($data['front']),
            ]);
            $card->save();

            if ($lesson !== null) {
                $card->lessons()->attach($lesson->id);
            }

            return $card;
        });
    }

    /**
     * @param  array{front: string, back: string, example?: string|null, example_translation?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function update(User $user, Card $card, array $data): void
    {
        $this->ensureUnique($user, $data['front'], $data['back'], $card);

        $card->forceFill([...$this->content($data), 'kind' => $this->kindOf($data['front'])])->save();
    }

    /**
     * Suspenso, o cartão sai das sessões até o aluno reativar (FLA-06).
     */
    public function suspend(Card $card): void
    {
        $card->forceFill(['suspended_at' => $card->suspended_at ?? now()])->save();
    }

    public function resume(Card $card): void
    {
        $card->forceFill(['suspended_at' => null])->save();
    }

    public function delete(Card $card): void
    {
        DB::transaction(function () use ($card) {
            $card->reports()->delete();
            $card->delete();
        });
    }

    /**
     * @param  array{front: string, back: string, example?: string|null, example_translation?: string|null}  $data
     * @return array<string, string|null>
     */
    private function content(array $data): array
    {
        $optional = fn (?string $value) => $value === null || trim($value) === '' ? null : trim($value);

        return [
            'front' => trim($data['front']),
            'back' => trim($data['back']),
            'example' => $optional($data['example'] ?? null),
            'example_translation' => $optional($data['example_translation'] ?? null),
        ];
    }

    private function kindOf(string $front): CardKind
    {
        return str_word_count($front) > 4 ? CardKind::Phrase : CardKind::Word;
    }

    /**
     * @throws ValidationException
     */
    private function ensureUnique(User $user, string $front, string $back, ?Card $except = null): void
    {
        $exists = Card::forUser($user)
            ->where('dedupe_key', CardIdentity::key($front, $back))
            ->when($except, fn ($query, Card $card) => $query->whereKeyNot($card->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'front' => 'Você já tem um cartão com esta frente e este significado.',
            ]);
        }
    }
}
