<?php

namespace App\Models;

use App\Domain\Cards\CardIdentity;
use App\Domain\Lessons\Reports\Reportable;
use App\Domain\Vocabulary\WordOrigin;
use App\Domain\Vocabulary\WordState;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Database\Factories\WordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Palavra do vocabulário do aluno (J3-B7, VOC-01): termo, classe, tradução,
 * definição simples, 2 exemplos, colocações, aula de origem e estado. O
 * estado acompanha o cartão da palavra (VOC-02).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int|null $source_lesson_id
 * @property WordOrigin $origin
 * @property string $language
 * @property string $term
 * @property string|null $part_of_speech
 * @property string $translation
 * @property string|null $definition
 * @property list<array{target: string, translation: string}>|null $examples
 * @property list<string>|null $collocations
 * @property WordState $state
 * @property string $dedupe_key
 * @property string|null $provider
 * @property string|null $model
 * @property string|null $prompt_version
 * @property bool $reported
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Word extends Model implements Reportable
{
    /** @use HasFactory<WordFactory> */
    use BelongsToUser, HasFactory, HasPublicUlid;

    public const ITEM = 'word';

    /** @var array<string, mixed> */
    protected $attributes = [
        'origin' => 'lesson',
        'state' => 'new',
        'reported' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (Word $word): void {
            $word->dedupe_key = CardIdentity::key($word->term, $word->translation);
        });
    }

    protected function casts(): array
    {
        return [
            'origin' => WordOrigin::class,
            'state' => WordState::class,
            'examples' => 'array',
            'collocations' => 'array',
            'reported' => 'boolean',
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
     * @return HasOne<Card, $this>
     */
    public function card(): HasOne
    {
        return $this->hasOne(Card::class);
    }

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
     * @return list<string>
     */
    public function itemKeys(): array
    {
        return [self::ITEM];
    }

    public function sourceLessonId(): ?int
    {
        return $this->source_lesson_id;
    }

    public function refreshReported(): void
    {
        $this->forceFill(['reported' => $this->reports()->open()->exists()])->save();
    }
}
