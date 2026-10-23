<?php

namespace App\Domain\Cards;

use App\Domain\Cards\Scheduling\CardState;
use App\Domain\Cards\Scheduling\Rating;
use App\Domain\Cards\Scheduling\ReviewScheduler;
use App\Domain\Vocabulary\WordBook;
use App\Models\Card;
use App\Models\CardReview;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra a nota do aluno num cartão (J3-B5, FLA-03): aplica o agendador,
 * guarda o novo estado e a resposta em card_reviews. A mesma resposta
 * reenviada (mesmo client_review_id) não conta duas vezes.
 */
final class ReviewCard
{
    public function __construct(private ReviewScheduler $scheduler, private WordBook $words) {}

    public function handle(User $user, Card $card, Rating $rating, ?int $durationMs = null, ?string $clientReviewId = null): CardReview
    {
        return DB::transaction(function () use ($user, $card, $rating, $durationMs, $clientReviewId) {
            if ($clientReviewId !== null) {
                $repeated = CardReview::forUser($user)->where('client_review_id', $clientReviewId)->first();

                if ($repeated !== null) {
                    return $repeated;
                }
            }

            $card = Card::forUser($user)->lockForUpdate()->findOrFail($card->id);
            $now = CarbonImmutable::now();
            $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;
            $wasNew = $card->first_reviewed_at === null;

            $next = $this->scheduler->schedule(self::stateOf($card), $rating, $now, $timezone);

            $card->forceFill([
                'repetitions' => $next->repetitions,
                'interval_days' => $next->intervalDays,
                'ease' => $next->ease,
                'lapses' => $next->lapses,
                'due_at' => $next->dueAt,
                'first_reviewed_at' => $card->first_reviewed_at ?? $now,
                'last_reviewed_at' => $now,
            ]);
            $intervalBefore = (int) $card->getOriginal('interval_days');
            $card->save();

            $review = tap((new CardReview)->forceFill([
                'user_id' => $user->id,
                'card_id' => $card->id,
                'rating' => $rating,
                'duration_ms' => $durationMs,
                'interval_before' => $intervalBefore,
                'interval_after' => $next->intervalDays,
                'ease_after' => $next->ease,
                'was_new' => $wasNew,
                'client_review_id' => $clientReviewId,
                'reviewed_at' => $now,
            ]))->save();

            $this->words->refreshState($card);

            return $review;
        });
    }

    public static function stateOf(Card $card): CardState
    {
        return new CardState(
            repetitions: $card->repetitions,
            intervalDays: $card->interval_days,
            ease: $card->ease,
            lapses: $card->lapses,
            dueAt: $card->due_at === null ? null : CarbonImmutable::instance($card->due_at),
        );
    }
}
