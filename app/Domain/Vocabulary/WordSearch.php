<?php

namespace App\Domain\Vocabulary;

use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Busca e filtros do vocabulário (J3-B9, VOC-01). A busca ignora acentos e
 * maiúsculas, no termo e na tradução, e tolera um erro de digitação pela
 * semelhança de trigramas do PostgreSQL.
 */
final class WordSearch
{
    public const TYPO_SIMILARITY = 0.4;

    /**
     * @param  array{q?: string|null, state?: string|null, lesson?: string|null, origin?: string|null}  $filters
     * @return Builder<Word>
     */
    public function query(User $user, array $filters): Builder
    {
        $query = Word::forUser($user);
        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(fn (Builder $query) => $query
                ->whereRaw('fixa_search_text(term) like fixa_search_text(?)', [$like])
                ->orWhereRaw('fixa_search_text(translation) like fixa_search_text(?)', [$like])
                ->orWhereRaw('similarity(fixa_search_text(term), fixa_search_text(?)) > ?', [$term, self::TYPO_SIMILARITY]))
                ->orderByRaw('greatest(similarity(fixa_search_text(term), fixa_search_text(?)), similarity(fixa_search_text(translation), fixa_search_text(?))) desc', [$term, $term]);
        }

        if (($state = WordState::tryFrom((string) ($filters['state'] ?? ''))) !== null) {
            $query->where('state', $state);
        }

        if (($origin = WordOrigin::tryFrom((string) ($filters['origin'] ?? ''))) !== null) {
            $query->where('origin', $origin);
        }

        if (! empty($filters['lesson'])) {
            // A aula é procurada só entre as do aluno (ACE-04).
            $lesson = Lesson::forUser($user)->where('ulid', $filters['lesson'])->first();

            $query->where(fn (Builder $query) => $lesson === null
                ? $query->whereRaw('false')
                : $query->where('source_lesson_id', $lesson->id)
                    ->orWhereIn('id', DB::table('lesson_word')->where('lesson_id', $lesson->id)->select('word_id')));
        }

        return $query->orderByRaw('fixa_search_text(term)')->orderBy('id');
    }
}
