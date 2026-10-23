<?php

namespace App\Models;

use App\Domain\Practice\Verdict;
use App\Models\Concerns\BelongsToUser;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Resposta do aluno a um item (J4-B4, ATV-03), com a correção e o XP.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $practice_item_id
 * @property string $answer
 * @property array<string, string|null> $correction
 * @property Verdict $verdict
 * @property string $source
 * @property int $xp
 * @property Carbon $answered_at
 */
#[Hidden(['answer', 'correction'])]
class Attempt extends Model
{
    use BelongsToUser, HasPublicUlid;

    protected function casts(): array
    {
        return [
            'answer' => 'encrypted',
            'correction' => 'encrypted:array',
            'verdict' => Verdict::class,
            'xp' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PracticeItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PracticeItem::class, 'practice_item_id');
    }
}
