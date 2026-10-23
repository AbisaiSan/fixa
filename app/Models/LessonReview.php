<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Database\Factories\LessonReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Revisão da aula (REV-01 a REV-04): gerada uma vez e guardada. Guarda a aula
 * de origem, o provedor e a versão do prompt (MAT-03, Modelo de dados).
 *
 * Itens que o aluno pode reportar (MAT-05): o resumo, cada conceito (pela
 * chave estável) e a lista de erros comuns. "reported" diz se há algum
 * report aberto.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $lesson_id
 * @property string $summary
 * @property list<array{key: string, name: string, explanation: string, examples: list<array{target: string, translation: string}>}> $concepts
 * @property list<array{wrong: string, right: string, why: string}> $common_mistakes
 * @property list<string> $can_do
 * @property string $provider
 * @property string $model
 * @property string $prompt_version
 * @property bool $reported
 * @property Carbon|null $created_at
 */
class LessonReview extends Model
{
    /** @use HasFactory<LessonReviewFactory> */
    use BelongsToUser, HasFactory, HasPublicUlid;

    public const SUMMARY = 'summary';

    public const COMMON_MISTAKES = 'common_mistakes';

    protected function casts(): array
    {
        return [
            'summary' => 'encrypted',
            'concepts' => 'encrypted:array',
            'common_mistakes' => 'encrypted:array',
            'can_do' => 'encrypted:array',
            'reported' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Ponto revisado: a revisão já é do aluno, e seus reports também. Sem o
     * escopo, a relação funciona na fila, onde não há aluno autenticado.
     *
     * @return MorphMany<ItemReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ItemReport::class, 'reportable')
            ->withoutGlobalScope(OwnedByAuthenticatedUser::class)
            ->where('item_reports.user_id', $this->user_id);
    }

    /**
     * Chave do item de um conceito, usada nos reports.
     */
    public static function conceptItem(string $key): string
    {
        return "concept:{$key}";
    }

    /**
     * Dá uma chave estável aos conceitos que ainda não têm.
     *
     * @param  list<array<string, mixed>>  $concepts
     * @return list<array<string, mixed>>
     */
    public static function withConceptKeys(array $concepts): array
    {
        return array_map(
            fn (array $concept) => ['key' => $concept['key'] ?? Str::lower(Str::random(10))] + $concept,
            $concepts,
        );
    }

    /**
     * @return list<string>
     */
    public function itemKeys(): array
    {
        return [
            self::SUMMARY,
            ...array_map(fn (array $concept) => self::conceptItem($concept['key']), $this->concepts),
            self::COMMON_MISTAKES,
        ];
    }

    public function hasConcept(string $key): bool
    {
        return in_array($key, array_column($this->concepts, 'key'), true);
    }

    /**
     * Acompanha se ainda há algum item em análise.
     */
    public function refreshReported(): void
    {
        $this->forceFill(['reported' => $this->reports()->open()->exists()])->save();
    }
}
