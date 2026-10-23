<?php

namespace App\Domain\Vocabulary;

use App\Domain\Cards\CardIdentity;
use App\Domain\Cards\Scheduling\Rating;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;

/**
 * Vocabulário do aluno (J3-B7, VOC-01, VOC-02). A mesma palavra com o mesmo
 * significado é uma só e ganha a referência a cada aula em que aparece.
 */
final class WordBook
{
    /**
     * Guarda a palavra que veio da aula, ou acha a que o aluno já tem.
     *
     * @param  array{part_of_speech: string, definition: string, examples: list<array{target: string, translation: string}>, collocations?: list<string>}  $details
     * @param  array{provider: string, model: string, prompt_version: string}  $generation
     */
    public function fromLesson(User $user, Lesson $lesson, string $term, string $translation, array $details, array $generation): Word
    {
        $word = Word::forUser($user)->where('dedupe_key', CardIdentity::key($term, $translation))->first();

        if ($word === null) {
            $word = (new Word)->forceFill([
                'user_id' => $user->id,
                'source_lesson_id' => $lesson->id,
                'origin' => WordOrigin::Lesson,
                'language' => $lesson->language,
                'term' => trim($term),
                'translation' => trim($translation),
                'part_of_speech' => trim($details['part_of_speech']),
                'definition' => trim($details['definition']),
                'examples' => $details['examples'],
                'collocations' => $details['collocations'] ?? [],
                ...$generation,
            ]);
            $word->save();
        }

        DB::table('lesson_word')->insertOrIgnore(['word_id' => $word->id, 'lesson_id' => $lesson->id, 'created_at' => now()]);

        return $word;
    }

    /**
     * Recalcula o estado depois de uma resposta ao cartão da palavra (VOC-02).
     */
    public function refreshState(Card $card): void
    {
        if ($card->word_id === null) {
            return;
        }

        $ratings = $card->reviews()
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(WordState::CHECKED_ANSWERS)
            ->pluck('rating')
            ->map(fn (Rating|int $rating) => $rating instanceof Rating ? $rating : Rating::from($rating))
            ->all();

        Word::withoutGlobalScopes()
            ->whereKey($card->word_id)
            ->where('user_id', $card->user_id)
            ->update(['state' => WordState::forCard($card->interval_days, array_values($ratings))->value]);
    }
}
