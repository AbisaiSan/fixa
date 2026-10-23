<?php

namespace App\Domain\Cards;

use App\Models\Card;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Fila de cartões do dia (J3-B4, FLA-04): vencidos primeiro, do mais
 * atrasado ao mais recente, e depois até 15 novos por dia (configurável),
 * contando os que já entraram hoje. O dia é o do fuso do aluno (GAM-06).
 * Cartões suspensos ou reportados ficam de fora (FLA-06, MAT-05).
 */
final class DailyQueue
{
    /**
     * @return Collection<int, Card>
     */
    public function for(User $user, ?Lesson $lesson = null, bool $dueOnly = false): Collection
    {
        $now = CarbonImmutable::now();

        $due = $this->routine($user, $lesson)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', $now)
            ->orderBy('due_at')
            ->orderBy('id')
            ->get();

        if ($dueOnly) {
            return $due;
        }

        $new = $this->routine($user, $lesson)
            ->whereNull('due_at')
            ->whereNull('first_reviewed_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($this->newCardsLeft($user, $now))
            ->get();

        return $due->concat($new);
    }

    /**
     * Novos que ainda cabem hoje.
     */
    public function newCardsLeft(User $user, CarbonImmutable $now): int
    {
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;
        $startOfDay = $now->setTimezone($timezone)->startOfDay()->utc();

        $startedToday = Card::forUser($user)->where('first_reviewed_at', '>=', $startOfDay)->count();

        return max(0, (int) config('fixa.cards.new_per_day', 15) - $startedToday);
    }

    /**
     * @return Builder<Card>
     */
    private function routine(User $user, ?Lesson $lesson): Builder
    {
        return Card::forUser($user)
            ->whereNull('suspended_at')
            ->where('reported', false)
            ->when($lesson, fn (Builder $query, Lesson $lesson) => $query->where(
                fn (Builder $query) => $query
                    ->where('source_lesson_id', $lesson->id)
                    ->orWhereIn('id', Card::idsInLesson($lesson)),
            ));
    }
}
