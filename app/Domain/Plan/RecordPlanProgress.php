<?php

namespace App\Domain\Plan;

use App\Domain\Cards\DailyQueue;
use App\Domain\Practice\SessionKind;
use App\Domain\Progress\XpLedger;
use App\Domain\Progress\XpSource;
use App\Models\Card;
use App\Models\CardReview;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Marca os blocos de hoje como feitos e o dia como contado (J5-B4, GAM-01).
 *
 * - Escrita, leitura ou ponto fraco concluídos fecham o bloco do mesmo tipo.
 * - Flashcards fecham com a fila do dia vazia ou com 3 cartões por minuto
 *   planejado. O vocabulário da aula, com os cartões daquela aula.
 * - O dia conta ao fazer os minutos da meta (DayProgress).
 */
final class RecordPlanProgress
{
    public function __construct(private GenerateWeeklyPlan $generator, private DailyQueue $queue, private XpLedger $xp) {}

    public function cardReviewed(User $user): void
    {
        $day = $this->today($user);

        if ($day === null) {
            return;
        }

        $since = $this->startOfToday($user);

        foreach ($this->pending($day) as $task) {
            $lesson = $task->activity === LessonActivity::Vocabulary ? $this->lesson($user, $task) : null;

            $done = match (true) {
                $task->kind === TaskKind::Flashcards => $this->reviewsSince($user, $since)->count() >= $this->cardsFor($task)
                    || $this->queue->for($user)->isEmpty(),
                $lesson !== null => $this->reviewsSince($user, $since)
                    ->whereIn('card_id', Card::forUser($user)->where(fn (Builder $query) => $query
                        ->where('source_lesson_id', $lesson->id)
                        ->orWhereIn('id', Card::idsInLesson($lesson)))->select('id'))
                    ->count() >= $this->cardsFor($task)
                    || $this->queue->for($user, $lesson)->isEmpty(),
                default => false,
            };

            if ($done) {
                $this->complete($task);
            }
        }

        $this->refreshCounted($day);
    }

    public function sessionCompleted(User $user, PracticeSession $session): void
    {
        $day = $this->today($user);

        if ($day === null) {
            return;
        }

        $pending = $this->pending($day);

        $task = $session->kind === SessionKind::WeakPoint
            ? $pending->first(fn (PlanTask $task) => $task->kind === TaskKind::WeakPoint)
            // A atividade da aula feita fecha o bloco da aula, mesmo que o plano sugerisse outra.
            : $pending->first(fn (PlanTask $task) => $task->kind === TaskKind::Lesson && $task->activity?->value === $session->kind->value)
                ?? $pending->first(fn (PlanTask $task) => $task->kind === TaskKind::Lesson);

        if ($task !== null) {
            $this->complete($task);
        }

        $this->refreshCounted($day);
    }

    /**
     * Confere a meta do dia e grava quando ele passou a contar.
     */
    public function refreshCounted(PlanDay $day, bool $rush = false): void
    {
        if ($day->counted_at !== null) {
            return;
        }

        $tasks = $day->tasks()->get();
        $counted = DayProgress::counted(
            goal: $day->goal_minutes,
            planned: (int) $tasks->sum('minutes'),
            done: (int) $tasks->whereNotNull('completed_at')->sum('minutes'),
            rush: $rush,
        );

        if ($counted) {
            $day->forceFill(['counted_at' => CarbonImmutable::now()])->save();
        }
    }

    /**
     * O dia de hoje no plano, criando a semana se ainda não existe.
     */
    public function today(User $user): ?PlanDay
    {
        $enrollment = $user->activeEnrollment;

        if ($enrollment === null) {
            return null;
        }

        $this->generator->for($user);

        return PlanDay::forUser($user)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('date', CarbonImmutable::now($enrollment->timezone)->toDateString())
            ->first();
    }

    /**
     * @return Collection<int, PlanTask>
     */
    private function pending(PlanDay $day): Collection
    {
        return $day->tasks()->whereNull('completed_at')->get();
    }

    private function complete(PlanTask $task): void
    {
        $task->forceFill(['completed_at' => CarbonImmutable::now()])->save();

        // Bloco de flashcards concluído vale como sessão (GAM-03).
        if ($task->kind === TaskKind::Flashcards) {
            /** @var User $user */
            $user = $task->user;
            $this->xp->award($user, XpSource::Flashcards, $task->id, XpSource::FLASHCARDS_XP);
        }
    }

    private function cardsFor(PlanTask $task): int
    {
        return $task->minutes * (int) config('fixa.plan.cards_per_minute', 3);
    }

    private function startOfToday(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($user->activeEnrollment->timezone ?? 'UTC')->startOfDay()->utc();
    }

    /**
     * @return Builder<CardReview>
     */
    private function reviewsSince(User $user, CarbonImmutable $since): Builder
    {
        return CardReview::forUser($user)->where('reviewed_at', '>=', $since);
    }

    private function lesson(User $user, PlanTask $task): ?Lesson
    {
        return $task->source_lesson_id === null ? null : Lesson::forUser($user)->find($task->source_lesson_id);
    }
}
