<?php

namespace App\Domain\Plan;

use App\Domain\Cards\DailyQueue;
use App\Domain\Progress\XpLedger;
use App\Domain\Progress\XpSource;
use App\Models\CardReview;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Modo corrida (J5-B5, PLA-04): o servidor marca o início ao abrir a sessão
 * e, ao terminar, confere a regra antes de contar o dia (GAM-01).
 */
final class RushMode
{
    public function __construct(private RecordPlanProgress $progress, private DailyQueue $queue, private XpLedger $xp) {}

    public function start(User $user): void
    {
        $day = $this->progress->today($user);

        // Recarregar a página não reinicia o relógio da corrida de hoje.
        if ($day !== null && $day->rush_started_at === null) {
            $day->forceFill(['rush_started_at' => CarbonImmutable::now()])->save();
        }
    }

    /**
     * @return bool se a corrida contou o dia
     */
    public function finish(User $user): bool
    {
        $day = $this->progress->today($user);

        if ($day === null) {
            return false;
        }

        if ($day->rush_completed_at !== null) {
            return true;
        }

        $startedAt = $day->rush_started_at === null ? null : CarbonImmutable::instance($day->rush_started_at);
        $now = CarbonImmutable::now();
        $reviews = $startedAt === null ? 0 : CardReview::forUser($user)->where('reviewed_at', '>=', $startedAt)->count();

        if (! RushRule::completes($startedAt, $now, $reviews, $this->queue->for($user, dueOnly: true)->count())) {
            return false;
        }

        $day->forceFill(['rush_completed_at' => $now])->save();
        $this->xp->award($user, XpSource::Rush, $day->id, XpSource::FLASHCARDS_XP, $now);
        $this->progress->refreshCounted($day, rush: true);

        return true;
    }
}
