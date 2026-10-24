<?php

namespace App\Domain\Plan;

use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Aula nova entra no plano no mesmo dia (J5-B3, PLA-05). Os blocos ainda
 * por fazer de hoje até domingo são montados de novo com a aula mais
 * recente. O que já foi feito e o que foi remanejado ficam como estão.
 * Funciona sem aluno logado (fila).
 */
final class RefreshUpcomingPlan
{
    public function __construct(private GenerateWeeklyPlan $generator) {}

    public function for(User $user, ?CarbonImmutable $now = null): void
    {
        $enrollment = $user->activeEnrollment;

        // Sem plano na semana, o plano novo já nasce com a aula.
        if ($enrollment === null || $this->generator->for($user, $now)) {
            return;
        }

        $today = CarbonImmutable::parse(($now ?? CarbonImmutable::now())->setTimezone($enrollment->timezone)->toDateString());
        $monday = $today->startOfWeek(CarbonInterface::MONDAY);
        $drafts = WeeklyPlanGenerator::generate($this->generator->input($user, $enrollment, $monday, $today));

        DB::transaction(function () use ($user, $enrollment, $drafts) {
            $days = PlanDay::forUser($user)
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('date', array_map(fn (DayDraft $draft) => $draft->date, $drafts))
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (PlanDay $day) => $day->date->toDateString());

            foreach ($drafts as $draft) {
                $day = $days->get($draft->date);

                if ($day !== null) {
                    $this->rebuild($user, $day, $draft);
                }
            }
        });
    }

    private function rebuild(User $user, PlanDay $day, DayDraft $draft): void
    {
        $kept = $day->tasks()->get()->filter(fn (PlanTask $task) => $task->completed_at !== null || $task->moved_from !== null);

        PlanTask::forUser($user)
            ->where('plan_day_id', $day->id)
            ->whereNotIn('id', $kept->pluck('id'))
            ->delete();

        // Na ordem do plano: o bloco já feito fica no lugar do novo do mesmo tipo.
        $final = [];
        foreach ($draft->tasks as $task) {
            $done = $kept->filter(fn (PlanTask $kept) => $kept->completed_at !== null && $kept->moved_from === null && $kept->kind === $task->kind);
            array_push($final, ...($done->isEmpty() ? [$task] : $done->all()));
        }
        array_push($final, ...$kept->reject(fn (PlanTask $kept) => in_array($kept, $final, true))->all());

        // Posições temporárias para não bater na chave única ao reordenar.
        foreach ($kept as $task) {
            $task->forceFill(['position' => $task->position + 100])->save();
        }

        foreach ($final as $index => $task) {
            if ($task instanceof PlanTask) {
                $task->forceFill(['position' => $index + 1])->save();

                continue;
            }

            (new PlanTask)->forceFill([
                'user_id' => $user->id,
                'plan_day_id' => $day->id,
                'position' => $index + 1,
                'kind' => $task->kind,
                'activity' => $task->activity,
                'source_lesson_id' => $task->lessonId,
                'minutes' => $task->minutes,
                'important' => $task->important,
            ])->save();
        }
    }
}
