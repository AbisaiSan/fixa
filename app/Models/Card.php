<?php

namespace App\Models;

use App\Domain\Cards\CardIdentity;
use App\Domain\Cards\CardKind;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Cartão de repetição espaçada (FLA-01): frente, verso, exemplo com tradução
 * e aula de origem. Guarda quem gerou e se foi reportado (Modelo de dados).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int|null $source_lesson_id
 * @property CardKind $kind
 * @property string $front
 * @property string $back
 * @property string|null $example
 * @property string|null $example_translation
 * @property string|null $provider
 * @property string|null $model
 * @property string|null $prompt_version
 * @property bool $reported
 * @property string|null $dedupe_key
 * @property Carbon|null $suspended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use BelongsToUser, HasFactory, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'reported' => false,
    ];

    /**
     * A chave de deduplicação acompanha frente e verso (FLA-05).
     */
    protected static function booted(): void
    {
        static::saving(function (Card $card): void {
            $card->dedupe_key = CardIdentity::key($card->front, $card->back);
        });
    }

    protected function casts(): array
    {
        return [
            'kind' => CardKind::class,
            'reported' => 'boolean',
            'suspended_at' => 'datetime',
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
     * Todas as aulas em que o cartão aparece (FLA-05).
     *
     * @return BelongsToMany<Lesson, $this>
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)->withPivot('created_at');
    }

    /**
     * @return MorphMany<ItemReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ItemReport::class, 'reportable');
    }
}
