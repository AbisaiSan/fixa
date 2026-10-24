<?php

namespace App\Http\Controllers\Plan;

use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Plan\RecalculatePlan;
use App\Domain\Plan\RefreshUpcomingPlan;
use App\Domain\Progress\SettleStreak;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plano da semana (J5-F4, protótipo tela 11, fluxo C): os 7 dias com o
 * estado de cada um, os blocos do dia escolhido e a meta diária.
 */
class PlanController extends Controller
{
    public function show(Request $request, SettleStreak $streaks, GenerateWeeklyPlan $generator, RecalculatePlan $recalculator): Response
    {
        /** @var User $user */
        $user = $request->user();
        $enrollment = $user->activeEnrollment;
        abort_if($enrollment === null, 404);

        $streaks->for($user);
        $generator->for($user);
        $recalculator->for($user);

        $now = CarbonImmutable::now($enrollment->timezone);
        $today = $now->toDateString();
        $monday = $now->startOfWeek(CarbonInterface::MONDAY)->toDateString();

        $days = PlanDay::forUser($user)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('week_start', $monday)
            ->orderBy('date')
            ->get();

        $tasks = PlanTask::forUser($user)
            ->whereIn('plan_day_id', $days->pluck('id'))
            ->whereNull('rescheduled_at')
            ->with('sourceLesson:id,title,lesson_date')
            ->orderBy('position')
            ->get()
            ->groupBy('plan_day_id');

        $startOfToday = $now->startOfDay()->utc();

        return Inertia::render('app/Plan', [
            'today' => $today,
            'weekStart' => $monday,
            'goal' => $enrollment->daily_goal_minutes,
            'goalRange' => ['min' => Enrollment::MIN_DAILY_GOAL, 'max' => Enrollment::MAX_DAILY_GOAL, 'step' => 5],
            // O plano foi refeito hoje com tarefas remanejadas (fluxo C).
            'redone' => PlanTask::forUser($user)
                ->whereIn('plan_day_id', $days->pluck('id'))
                ->whereNotNull('moved_from')
                ->where('created_at', '>=', $startOfToday)
                ->exists(),
            'days' => $days->map(function (PlanDay $day) use ($tasks, $today) {
                $dayTasks = $tasks->get($day->id, collect());

                return [
                    'date' => $day->date->toDateString(),
                    'status' => $day->status($today)->value,
                    'goal' => $day->goal_minutes,
                    'minutes' => (int) $dayTasks->filter(fn (PlanTask $task) => $task->skipped_at === null || $task->completed_at !== null)->sum('minutes'),
                    'tasks' => $dayTasks->map(fn (PlanTask $task) => [
                        'ulid' => $task->ulid,
                        'kind' => $task->kind->value,
                        'activity' => $task->activity?->value,
                        'minutes' => $task->minutes,
                        'done' => $task->completed_at !== null,
                        'moved_from' => $task->moved_from?->toDateString(),
                        'lesson' => $task->sourceLesson === null ? null : [
                            'title' => $task->sourceLesson->title,
                            'lesson_date' => $task->sourceLesson->lesson_date->toDateString(),
                        ],
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    /**
     * Meta diária de 5 a 30 minutos (PLA-01). Vale a partir de amanhã.
     */
    public function updateGoal(Request $request, RefreshUpcomingPlan $refresh): RedirectResponse
    {
        $validated = $request->validate([
            'daily_goal_minutes' => ['required', 'integer', 'between:'.Enrollment::MIN_DAILY_GOAL.','.Enrollment::MAX_DAILY_GOAL, Rule::in(range(Enrollment::MIN_DAILY_GOAL, Enrollment::MAX_DAILY_GOAL, 5))],
        ]);

        /** @var User $user */
        $user = $request->user();
        $enrollment = $user->activeEnrollment;
        abort_if($enrollment === null, 404);

        $enrollment->forceFill(['daily_goal_minutes' => (int) $validated['daily_goal_minutes']])->save();
        $refresh->for($user->fresh() ?? $user, fromTomorrow: true);

        return back()->with('status', 'goal-updated');
    }
}
