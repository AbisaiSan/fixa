<?php

namespace App\Models;

use App\Domain\Plan\DayStatus;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use App\Models\Scopes\OwnedByAuthenticatedUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Um dia do plano da semana (J5-B1, PLA-01). A data é o dia do calendário
 * no fuso da matrícula (GAM-06).
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $enrollment_id
 * @property Carbon $date
 * @property Carbon $week_start
 * @property int $goal_minutes
 * @property Carbon|null $recalculated_at
 * @property Carbon|null $counted_at
 * @property Carbon|null $frozen_at
 * @property Carbon|null $rush_started_at
 * @property Carbon|null $rush_completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlanDay extends Model
{
    use BelongsToUser, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'week_start' => 'date',
            'goal_minutes' => 'integer',
            'recalculated_at' => 'datetime',
            'counted_at' => 'datetime',
            'frozen_at' => 'datetime',
            'rush_started_at' => 'datetime',
            'rush_completed_at' => 'datetime',
        ];
    }

    public function status(string $today): DayStatus
    {
        return DayStatus::of($this->date->toDateString(), $today, $this->counted_at !== null, $this->frozen_at !== null);
    }

    /**
     * @return BelongsTo<Enrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class)->withoutGlobalScope(OwnedByAuthenticatedUser::class);
    }

    /**
     * Ponto revisado: os blocos são do dono do dia, também fora de uma requisição.
     *
     * @return HasMany<PlanTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(PlanTask::class)
            ->withoutGlobalScope(OwnedByAuthenticatedUser::class)
            ->where('plan_tasks.user_id', $this->user_id)
            ->orderBy('position');
    }
}
