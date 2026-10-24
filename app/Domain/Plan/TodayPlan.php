<?php

namespace App\Domain\Plan;

use App\Domain\Cards\DailyQueue;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\SessionStatus;
use App\Domain\Practice\WeakPointBuilder;
use App\Domain\Progress\Level;
use App\Domain\Progress\ResumeMessage;
use App\Domain\Progress\SettleStreak;
use App\Domain\Progress\StreakRules;
use App\Domain\Progress\XpLedger;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * O que a tela Hoje mostra (J5-B9, PLA-06): o plano do dia com o próximo
 * bloco a 1 clique, o anel da meta, a sequência, os congelamentos, o XP e a
 * mensagem de retomada. Antes de montar, fecha os dias que passaram, garante
 * o plano da semana e refaz o dia se o aluno faltou.
 */
/**
 * @phpstan-type Block array{ulid: string, kind: string, activity: string|null, minutes: int, count: int, done: bool, skipped: bool, moved_from: string|null, href: string|null, lesson: array{ulid: string, title: string, lesson_date: string}|null}
 * @phpstan-type Today array{date: string, goal: array{minutes: int, done: int, planned: int, percent: int, counted: bool}, blocks: list<Block>, next: string|null, streak: array{current: int, freezes: int, max_freezes: int}, xp: array{total: int, level: int}, rush: array{due: int, done: bool}, resume: string|null}
 */
final class TodayPlan
{
    public function __construct(
        private SettleStreak $streaks,
        private GenerateWeeklyPlan $generator,
        private RecalculatePlan $recalculator,
        private RecordPlanProgress $progress,
        private DailyQueue $queue,
        private XpLedger $xp,
    ) {}

    /**
     * @return Today|null nulo sem matrícula ativa
     */
    public function for(User $user): ?array
    {
        $enrollment = $user->activeEnrollment;

        if ($enrollment === null) {
            return null;
        }

        $streak = $this->streaks->for($user);
        $this->generator->for($user);
        $this->recalculator->for($user);

        $today = CarbonImmutable::now($enrollment->timezone)->toDateString();
        $day = $this->progress->today($user);
        $blocks = $day === null ? [] : $day->tasks()->get()->map(fn (PlanTask $task) => $this->block($user, $task))->all();

        if ($day !== null) {
            $this->progress->refreshCounted($day);
            $day->refresh();
        }

        $active = array_filter($blocks, fn (array $block) => ! $block['skipped'] || $block['done']);
        $done = array_sum(array_map(fn (array $block) => $block['done'] ? $block['minutes'] : 0, $blocks));
        $counted = $day?->counted_at !== null;
        $next = collect($blocks)->first(fn (array $block) => ! $block['done'] && ! $block['skipped']);
        $xp = $this->xp->total($user);
        $level = Level::of($xp);
        $dueCards = $this->queue->for($user, dueOnly: true)->count();
        $state = $streak?->state();

        return [
            'date' => $today,
            'goal' => [
                'minutes' => $enrollment->daily_goal_minutes,
                'done' => $done,
                'planned' => array_sum(array_map(fn (array $block) => $block['minutes'], $active)),
                'percent' => DayProgress::percent($enrollment->daily_goal_minutes, $done),
                'counted' => $counted,
            ],
            'blocks' => array_values($blocks),
            'next' => $next['ulid'] ?? null,
            'streak' => [
                'current' => $state === null ? 0 : StreakRules::shown($state, $counted),
                'freezes' => $state === null ? 0 : StreakRules::shownFreezes($state, $today),
                'max_freezes' => StreakRules::MAX_FREEZES,
            ],
            'xp' => ['total' => $xp, 'level' => $level->level],
            'rush' => ['due' => $dueCards, 'done' => $day?->rush_completed_at !== null],
            'resume' => ResumeMessage::for(
                lastStudied: $this->lastStudied($user, $today),
                today: $today,
                yesterdayFrozen: PlanDay::forUser($user)->whereDate('date', CarbonImmutable::parse($today)->subDay()->toDateString())->whereNotNull('frozen_at')->exists(),
            )?->value,
        ];
    }

    /**
     * Um bloco com o link para começar, resolvido agora: atividade já feita
     * em outro dia dá lugar à próxima da aula, e bloco sem nada a fazer hoje
     * fica de lado, sem pesar na meta.
     *
     * @return Block
     */
    private function block(User $user, PlanTask $task): array
    {
        $lesson = $task->source_lesson_id === null ? null : Lesson::forUser($user)->find($task->source_lesson_id);
        [$activity, $href, $count] = match ($task->kind) {
            TaskKind::Flashcards => [null, route('flashcards'), $this->queue->for($user)->count()],
            TaskKind::WeakPoint => [null, route('practice.weak-point'), $this->weakPointItems($user)],
            TaskKind::Lesson => $this->lessonTarget($user, $task, $lesson),
        };

        $done = $task->completed_at !== null;

        if (! $done && $count === 0 && $task->skipped_at === null) {
            $task->forceFill(['skipped_at' => CarbonImmutable::now()])->save();
        } elseif ($count > 0 && $task->skipped_at !== null && ! $done) {
            // Voltou a ter o que fazer (uma aula nova, um erro que voltou).
            $task->forceFill(['skipped_at' => null])->save();
        }

        return [
            'ulid' => $task->ulid,
            'kind' => $task->kind->value,
            'activity' => $activity?->value,
            'minutes' => $task->minutes,
            'count' => $count,
            'done' => $done,
            'skipped' => ! $done && $count === 0,
            'moved_from' => $task->moved_from?->toDateString(),
            'href' => $done || $count === 0 ? null : $href,
            'lesson' => $lesson === null ? null : [
                'ulid' => $lesson->ulid,
                'title' => $lesson->title,
                'lesson_date' => $lesson->lesson_date->toDateString(),
            ],
        ];
    }

    /**
     * @return array{0: LessonActivity|null, 1: string, 2: int}
     */
    private function lessonTarget(User $user, PlanTask $task, ?Lesson $lesson): array
    {
        if ($lesson === null) {
            return [null, route('practice'), 0];
        }

        $sessions = PracticeSession::forUser($user)
            ->where('source_lesson_id', $lesson->id)
            ->whereIn('kind', [SessionKind::Writing, SessionKind::Reading])
            ->where('status', '!=', SessionStatus::Completed)
            ->get()
            ->keyBy(fn (PracticeSession $session) => $session->kind->value);

        $order = array_unique([$task->activity ?? LessonActivity::Writing, LessonActivity::Writing, LessonActivity::Reading, LessonActivity::Vocabulary], SORT_REGULAR);

        foreach ($order as $activity) {
            $session = $sessions->get($activity->value);

            if ($activity === LessonActivity::Vocabulary) {
                $cards = $this->queue->for($user, $lesson)->count();

                if ($cards > 0) {
                    return [$activity, route('flashcards', ['aula' => $lesson->ulid]), $cards];
                }
            } elseif ($session !== null) {
                return [
                    $activity,
                    route($activity === LessonActivity::Writing ? 'practice.writing' : 'practice.reading', $session),
                    $session->items()->count(),
                ];
            }
        }

        return [$task->activity, route('lessons.show', $lesson), 0];
    }

    private function weakPointItems(User $user): int
    {
        $open = PracticeSession::forUser($user)
            ->where('kind', SessionKind::WeakPoint)
            ->whereIn('status', [SessionStatus::Ready, SessionStatus::InProgress])
            ->latest('id')
            ->first();

        if ($open !== null) {
            return $open->items()->count();
        }

        return min(WeakPointBuilder::MAX_ITEMS, Mistake::forUser($user)->open()->where('due_at', '<=', CarbonImmutable::now())->count());
    }

    private function lastStudied(User $user, string $today): ?string
    {
        $date = PlanDay::forUser($user)
            ->whereNotNull('counted_at')
            ->whereDate('date', '<', $today)
            ->max('date');

        return $date === null ? null : CarbonImmutable::parse($date)->toDateString();
    }
}
