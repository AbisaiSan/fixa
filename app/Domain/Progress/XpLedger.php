<?php

namespace App\Domain\Progress;

use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Models\User;
use App\Models\XpEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registro de XP (J5-B7, GAM-03). Cada origem rende XP uma vez só, e nada
 * aqui subtrai: o total é a soma dos eventos, que nunca mudam.
 */
final class XpLedger
{
    public function award(User $user, XpSource $source, int $sourceId, int $xp, ?CarbonImmutable $at = null): void
    {
        if ($xp <= 0) {
            return;
        }

        $now = CarbonImmutable::now();

        XpEvent::query()->insertOrIgnore([
            'user_id' => $user->id,
            'source' => $source->value,
            'source_id' => $sourceId,
            'xp' => $xp,
            'earned_at' => $at ?? $now,
            'created_at' => $now,
        ]);
    }

    public function total(User $user): int
    {
        return (int) XpEvent::forUser($user)->sum('xp');
    }

    /**
     * Recalcula o registro a partir do histórico (cartões, respostas, sessões,
     * blocos e corridas), sem duplicar o que já está lá. Sem aluno, refaz todos.
     */
    public function rebuild(?User $user = null): int
    {
        $before = (int) DB::table('xp_events')->count();
        $only = $user === null ? '' : 'AND src.user_id = '.(int) $user->id;
        $completed = SessionStatus::Completed->value;
        $reading = SessionKind::Reading->value;

        $sources = [
            [XpSource::CardReview, 'card_reviews', (string) XpSource::CARD_REVIEW_XP, 'src.reviewed_at', ''],
            [XpSource::Attempt, 'attempts', 'src.xp', 'src.answered_at', 'AND src.xp > 0'],
            [XpSource::Session, 'practice_sessions', "CASE WHEN src.kind = '{$reading}' THEN ".XpSource::READING_XP.' ELSE '.XpSource::SESSION_XP.' END', 'src.completed_at', "AND src.status = '{$completed}' AND src.completed_at IS NOT NULL"],
            [XpSource::Flashcards, 'plan_tasks', (string) XpSource::FLASHCARDS_XP, 'src.completed_at', "AND src.kind = 'flashcards' AND src.completed_at IS NOT NULL"],
            [XpSource::Rush, 'plan_days', (string) XpSource::FLASHCARDS_XP, 'src.rush_completed_at', 'AND src.rush_completed_at IS NOT NULL'],
        ];

        foreach ($sources as [$source, $table, $xp, $at, $where]) {
            DB::statement(
                "INSERT INTO xp_events (user_id, source, source_id, xp, earned_at, created_at)
                 SELECT src.user_id, ?, src.id, {$xp}, {$at}, now() FROM {$table} src
                 WHERE 1 = 1 {$where} {$only}
                 ON CONFLICT (user_id, source, source_id) DO NOTHING",
                [$source->value],
            );
        }

        return (int) DB::table('xp_events')->count() - $before;
    }
}
