<?php

namespace App\Http\Controllers\Progress;

use App\Domain\Progress\Level;
use App\Domain\Progress\SettleStreak;
use App\Domain\Progress\StreakRules;
use App\Domain\Progress\WeeklyGoal;
use App\Domain\Progress\XpLedger;
use App\Http\Controllers\Controller;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Progresso (J5-F5, protótipo tela 12, GAM-01 a GAM-05): sequência e
 * recorde, congelamentos, meta semanal, nível com barra e as últimas
 * semanas. Sem ranking: o aluno só se compara com ele mesmo.
 */
class ProgressController extends Controller
{
    public const HISTORY_WEEKS = 5;

    public function show(Request $request, SettleStreak $streaks, XpLedger $ledger): Response
    {
        /** @var User $user */
        $user = $request->user();
        $enrollment = $user->activeEnrollment;
        abort_if($enrollment === null, 404);

        $streak = $streaks->for($user);
        $now = CarbonImmutable::now($enrollment->timezone);
        $today = $now->toDateString();
        $monday = $now->startOfWeek(CarbonInterface::MONDAY);
        $oldest = $monday->subWeeks(self::HISTORY_WEEKS - 1);

        $days = PlanDay::forUser($user)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('date', '>=', $oldest->toDateString())
            ->whereDate('date', '<=', $monday->addDays(6)->toDateString())
            ->get()
            ->keyBy(fn (PlanDay $day) => $day->date->toDateString());

        $minutes = PlanTask::forUser($user)
            ->whereIn('plan_day_id', $days->pluck('id'))
            ->whereNotNull('completed_at')
            ->get()
            ->groupBy('plan_day_id')
            ->map(fn ($tasks) => (int) $tasks->sum('minutes'));

        $todayCounted = $days->get($today)?->counted_at !== null;
        $state = $streak?->state();
        $current = $state === null ? 0 : StreakRules::shown($state, $todayCounted);
        $xp = $ledger->total($user);
        $level = Level::of($xp);

        $week = [];
        for ($date = $monday; $date->lte($monday->addDays(6)); $date = $date->addDay()) {
            $key = $date->toDateString();
            $day = $days->get($key);
            $week[] = [
                'date' => $key,
                'studied' => $day?->counted_at !== null,
                'status' => $day?->status($today)->value ?? ($key > $today ? 'planned' : ($key === $today ? 'today' : 'missed')),
            ];
        }
        $studied = count(array_filter($week, fn (array $day) => $day['studied']));

        $history = [];
        for ($start = $oldest; $start->lte($monday); $start = $start->addWeek()) {
            $from = $start->toDateString();
            $to = $start->addDays(6)->toDateString();
            // Compara as datas do calendário, sem fuso no meio.
            $weekDays = $days->filter(fn (PlanDay $day) => $day->date->toDateString() >= $from && $day->date->toDateString() <= $to);
            $history[] = [
                'start' => $from,
                'end' => $to,
                'days' => $weekDays->whereNotNull('counted_at')->count(),
                'minutes' => (int) $weekDays->sum(fn (PlanDay $day) => $minutes->get($day->id, 0)),
            ];
        }

        $longest = max($streak->longest ?? 0, $current);

        return Inertia::render('app/Progress', [
            'status' => $xp === 0 && $longest === 0 && $studied === 0 ? 'empty' : 'ready',
            'streak' => [
                'current' => $current,
                'longest' => $longest,
                'freezes' => $state === null ? 0 : StreakRules::shownFreezes($state, $today),
                'max_freezes' => StreakRules::MAX_FREEZES,
            ],
            'week' => ['days' => $week, 'studied' => $studied, 'goal' => WeeklyGoal::DAYS],
            'level' => [
                'level' => $level->level,
                'xp' => $xp,
                'in_level' => $level->inLevel,
                'to_next' => $level->toNext,
                'per_level' => Level::XP_PER_LEVEL,
            ],
            'history' => $history,
        ]);
    }
}
