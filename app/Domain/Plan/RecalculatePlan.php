<?php

namespace App\Domain\Plan;

use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Refaz a semana depois de um dia sem estudo (J5-B2, PLA-03), uma vez por
 * aluno e por dia no fuso da matrícula (GAM-06). As tarefas atrasadas da
 * semana são remanejadas para os dias seguintes ou descartadas. Funciona sem
 * aluno logado (comando de hora em hora).
 */
final class RecalculatePlan
{
    /**
     * @return bool se recalculou agora
     */
    public function for(User $user, ?CarbonImmutable $now = null): bool
    {
        $enrollment = $user->activeEnrollment;

        if ($enrollment === null) {
            return false;
        }

        $now ??= CarbonImmutable::now();
        $today = $now->setTimezone($enrollment->timezone)->toDateString();

        return DB::transaction(function () use ($user, $enrollment, $today, $now) {
            $todayDay = PlanDay::forUser($user)
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($todayDay === null || $todayDay->recalculated_at !== null) {
                return false;
            }

            $week = PlanDay::forUser($user)
                ->where('enrollment_id', $enrollment->id)
                ->whereDate('week_start', $todayDay->week_start->toDateString())
                ->orderBy('date')
                ->get();

            $overdue = PlanTask::forUser($user)
                ->whereIn('plan_day_id', $week->filter(fn (PlanDay $day) => $day->date->toDateString() < $today)->pluck('id'))
                ->whereNull('completed_at')
                ->whereNull('rescheduled_at')
                ->get()
                ->sortBy(fn (PlanTask $task) => [$week->firstWhere('id', $task->plan_day_id)?->date->toDateString(), $task->position])
                ->values();

            $upcoming = $week->filter(fn (PlanDay $day) => $day->date->toDateString() > $today)->values();

            $result = PlanRecalculator::recalculate(
                array_values($overdue->map(fn (PlanTask $task) => self::planned($task, $week->firstWhere('id', $task->plan_day_id)))->all()),
                array_values($upcoming->map(fn (PlanDay $day) => new PlannedDay(
                    $day->date->toDateString(),
                    $day->goal_minutes,
                    array_values($day->tasks()->get()->map(fn (PlanTask $task) => self::planned($task, $day))->all()),
                ))->all()),
            );

            foreach ($result->moves as $taskId => $date) {
                /** @var PlanTask $task */
                $task = $overdue->firstWhere('id', $taskId);
                /** @var PlanDay $target */
                $target = $upcoming->first(fn (PlanDay $day) => $day->date->toDateString() === $date);

                (new PlanTask)->forceFill([
                    'user_id' => $user->id,
                    'plan_day_id' => $target->id,
                    'position' => (int) $target->tasks()->max('position') + 1,
                    'kind' => $task->kind,
                    'activity' => $task->activity,
                    'source_lesson_id' => $task->source_lesson_id,
                    'minutes' => $task->minutes,
                    'important' => true,
                    'moved_from' => $week->firstWhere('id', $task->plan_day_id)?->date->toDateString(),
                ])->save();
            }

            PlanTask::forUser($user)
                ->whereIn('id', [...array_keys($result->moves), ...$result->dropped])
                ->update(['rescheduled_at' => $now]);

            $todayDay->forceFill(['recalculated_at' => $now])->save();

            return true;
        });
    }

    private static function planned(PlanTask $task, ?PlanDay $day): PlannedTask
    {
        return new PlannedTask(
            id: $task->id,
            date: $day?->date->toDateString() ?? '',
            kind: $task->kind,
            activity: $task->activity,
            lessonId: $task->source_lesson_id,
            minutes: $task->minutes,
        );
    }
}
