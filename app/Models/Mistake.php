<?php

namespace App\Models;

use App\Domain\Practice\ErrorCategory;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Erro do caderno (J4-B8, ERR-01): o texto do aluno, a correção, a
 * categoria, a aula e as repetições. Volta nas atividades em 1, 3 e 7 dias
 * e sai depois de 3 acertos seguidos no mesmo ponto (ERR-02, ERR-03).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int|null $source_lesson_id
 * @property ErrorCategory $category
 * @property string $point_key
 * @property array{point: string, answer: string, corrected: string, explanation: string, natural?: string} $content
 * @property int $repetitions
 * @property bool $priority
 * @property int $step
 * @property int $correct_streak
 * @property Carbon|null $due_at
 * @property Carbon $last_error_at
 * @property Carbon|null $resolved_at
 */
#[Hidden(['content', 'point_key'])]
class Mistake extends Model
{
    use BelongsToUser, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'category' => ErrorCategory::class,
            'content' => 'encrypted:array',
            'repetitions' => 'integer',
            'priority' => 'boolean',
            'step' => 'integer',
            'correct_streak' => 'integer',
            'due_at' => 'datetime',
            'last_error_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function sourceLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'source_lesson_id');
    }

    /**
     * Erros ainda na lista (ERR-03).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
