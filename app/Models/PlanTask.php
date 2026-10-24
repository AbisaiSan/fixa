<?php

namespace App\Models;

use App\Domain\Plan\LessonActivity;
use App\Domain\Plan\TaskKind;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Um bloco do dia (J5-B1, PLA-02): flashcards, atividade da aula ou ponto fraco.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $plan_day_id
 * @property int $position
 * @property TaskKind $kind
 * @property LessonActivity|null $activity
 * @property int|null $source_lesson_id
 * @property int $minutes
 * @property bool $important
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlanTask extends Model
{
    use BelongsToUser, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'important' => false,
    ];

    protected function casts(): array
    {
        return [
            'kind' => TaskKind::class,
            'activity' => LessonActivity::class,
            'position' => 'integer',
            'minutes' => 'integer',
            'important' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlanDay, $this>
     */
    public function planDay(): BelongsTo
    {
        return $this->belongsTo(PlanDay::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function sourceLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'source_lesson_id');
    }
}
