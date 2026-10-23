<?php

namespace App\Models;

use App\Domain\Lessons\Reports\Reportable;
use App\Domain\Practice\ItemContext;
use App\Domain\Practice\ItemType;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Item de uma sessão de prática (J4-B1, ATV-01). O conteúdo guarda o
 * enunciado, a frase, as respostas aceitas, a versão corrigida, a
 * alternativa natural e a explicação. O gabarito nunca vai para a tela
 * antes da resposta.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $practice_session_id
 * @property int $position
 * @property ItemType $type
 * @property ItemContext|null $context
 * @property array<string, mixed> $content
 * @property bool $reported
 * @property Carbon|null $created_at
 */
#[Hidden(['content'])]
class PracticeItem extends Model implements Reportable
{
    use BelongsToUser, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'reported' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'context' => ItemContext::class,
            'content' => 'encrypted:array',
            'position' => 'integer',
            'reported' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PracticeSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }

    public const ITEM = 'item';

    /**
     * Ponto revisado, como em Card::reports().
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
     * O item e a correção são reportados juntos (MAT-05).
     *
     * @return list<string>
     */
    public function itemKeys(): array
    {
        return [self::ITEM];
    }

    public function sourceLessonId(): ?int
    {
        return PracticeSession::withoutGlobalScopes()->whereKey($this->practice_session_id)->value('source_lesson_id');
    }

    public function refreshReported(): void
    {
        $this->forceFill(['reported' => $this->reports()->open()->exists()])->save();
    }
}
