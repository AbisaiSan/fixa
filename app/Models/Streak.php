<?php

namespace App\Models;

use App\Domain\Progress\StreakState;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Sequência e congelamentos de uma matrícula (J5-B6, GAM-01, GAM-02). Não
 * sai do servidor por rota própria, por isso não tem ULID.
 *
 * @property int $id
 * @property int $user_id
 * @property int $enrollment_id
 * @property int $current
 * @property int $longest
 * @property int $freezes
 * @property Carbon $settled_through
 * @property Carbon $last_grant_week
 */
class Streak extends Model
{
    use BelongsToUser;

    protected function casts(): array
    {
        return [
            'current' => 'integer',
            'longest' => 'integer',
            'freezes' => 'integer',
            'settled_through' => 'date',
            'last_grant_week' => 'date',
        ];
    }

    public function state(): StreakState
    {
        return new StreakState(
            current: $this->current,
            longest: $this->longest,
            freezes: $this->freezes,
            settledThrough: $this->settled_through->toDateString(),
            lastGrantWeek: $this->last_grant_week->toDateString(),
        );
    }
}
