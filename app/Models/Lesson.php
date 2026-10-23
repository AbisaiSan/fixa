<?php

namespace App\Models;

use App\Domain\Lessons\InvalidLessonTransition;
use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\LessonStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Aula enviada pelo aluno (J2-B2, MAT-01). Tudo o que é gerado aponta para ela.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $enrollment_id
 * @property string $title
 * @property Carbon $lesson_date
 * @property string $language
 * @property LessonSource $source
 * @property LessonStatus $status
 * @property string|null $failure_reason
 * @property string|null $extracted_text
 * @property int|null $word_count
 * @property Carbon $rights_declared_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'lesson_date'])]
#[Hidden(['extracted_text'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use BelongsToUser, HasFactory, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'queued',
    ];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'source' => LessonSource::class,
            'status' => LessonStatus::class,
            'extracted_text' => 'encrypted',
            'word_count' => 'integer',
            'rights_declared_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * @return HasMany<LessonFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(LessonFile::class)->orderBy('position');
    }

    /**
     * @return HasOne<LessonReview, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(LessonReview::class);
    }

    /**
     * Muda de estado só pelos caminhos permitidos.
     *
     * @throws InvalidLessonTransition
     */
    public function transitionTo(LessonStatus $next, ?string $failureReason = null): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new InvalidLessonTransition($this->status, $next);
        }

        $this->forceFill([
            'status' => $next,
            'failure_reason' => $next === LessonStatus::Failed ? $failureReason : null,
        ])->save();
    }
}
