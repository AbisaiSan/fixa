<?php

namespace App\Models;

use App\Domain\Lessons\Reports\ReportReason;
use App\Domain\Lessons\Reports\ReportStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * "Reportar erro" num item gerado (MAT-05). Enquanto aberto, o item sai da
 * rotina e aparece como "em análise". Os reports abertos alimentam a lista
 * de revisão da qualidade da IA.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int|null $source_lesson_id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property string $item_key
 * @property ReportReason $reason
 * @property string|null $details
 * @property ReportStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['details'])]
class ItemReport extends Model
{
    use BelongsToUser, HasPublicUlid;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'details' => 'encrypted',
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
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', ReportStatus::Open);
    }
}
