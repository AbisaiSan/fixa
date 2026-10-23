<?php

namespace App\Models;

use App\Domain\Cards\Scheduling\Rating;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Uma resposta a um cartão (J3-B5, FLA-03). Não sai do servidor por rota
 * própria, por isso não tem ULID.
 *
 * @property int $id
 * @property int $user_id
 * @property int $card_id
 * @property Rating $rating
 * @property int|null $duration_ms
 * @property int $interval_before
 * @property int $interval_after
 * @property float $ease_after
 * @property bool $was_new
 * @property string|null $client_review_id
 * @property Carbon $reviewed_at
 */
class CardReview extends Model
{
    use BelongsToUser;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'rating' => Rating::class,
            'duration_ms' => 'integer',
            'interval_before' => 'integer',
            'interval_after' => 'integer',
            'ease_after' => 'float',
            'was_new' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
