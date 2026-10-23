<?php

namespace App\Models;

use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Sessão de prática (J4-B11, ATV-01): escrita da aula, leitura ou ponto
 * fraco, com os itens em ordem.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int|null $source_lesson_id
 * @property SessionKind $kind
 * @property SessionStatus $status
 * @property string|null $provider
 * @property string|null $model
 * @property string|null $prompt_version
 * @property bool $reported
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 */
class PracticeSession extends Model
{
    use BelongsToUser, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'ready',
        'reported' => false,
    ];

    protected function casts(): array
    {
        return [
            'kind' => SessionKind::class,
            'status' => SessionStatus::class,
            'reported' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * Ponto revisado: os itens são do dono da sessão, também fora de uma requisição.
     *
     * @return HasMany<PracticeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PracticeItem::class)
            ->withoutGlobalScope(OwnedByAuthenticatedUser::class)
            ->where('practice_items.user_id', $this->user_id)
            ->orderBy('position');
    }
}
