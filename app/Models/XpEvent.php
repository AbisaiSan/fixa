<?php

namespace App\Models;

use App\Domain\Progress\XpSource;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Um ganho de XP (J5-B7, GAM-03). Só inserção: o XP nunca diminui, então
 * evento gravado não muda nem é apagado (só some com a conta do aluno).
 *
 * @property int $id
 * @property int $user_id
 * @property XpSource $source
 * @property int $source_id
 * @property int $xp
 * @property Carbon $earned_at
 */
class XpEvent extends Model
{
    use BelongsToUser;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('XP events are append only.'));
        static::deleting(fn () => throw new LogicException('XP events are append only.'));
    }

    protected function casts(): array
    {
        return [
            'source' => XpSource::class,
            'source_id' => 'integer',
            'xp' => 'integer',
            'earned_at' => 'datetime',
        ];
    }
}
