<?php

namespace App\Domain\Plan;

use App\Domain\Lessons\LessonStatus;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Grava o plano da semana corrente do aluno (J5-B1, PLA-01, PLA-02). A
 * semana é a do fuso da matrícula (GAM-06). Idempotente: quem já tem plano
 * na semana não ganha outro. Funciona sem aluno logado (fila e comando).
 */
final class GenerateWeeklyPlan
{
    /**
     * @return bool se o plano foi criado agora
     */
    public function for(User $user, ?CarbonImmutable $now = null): bool
    {
        $enrollment = $user->activeEnrollment;

        if ($enrollment === null) {
            return false;
        }

        $today = CarbonImmutable::parse(($now ?? CarbonImmutable::now())->setTimezone($enrollment->timezone)->toDateString());
        $monday = $today->startOfWeek(CarbonInterface::MONDAY);

        $exists = PlanDay::forUser($user)
            ->where('enrollment_id', $enrollment->id)
            ->whereDate('week_start', $monday->toDateString())
            ->exists();

        if ($exists) {
            return false;
        }

        $days = WeeklyPlanGenerator::generate($this->input($user, $enrollment, $monday, $today));

        try {
            DB::transaction(fn () => $this->store($user, $enrollment, $monday, $days));
        } catch (UniqueConstraintViolationException) {
            // Outro processo gravou a mesma semana ao mesmo tempo.
            return false;
        }

        return true;
    }

    private function input(User $user, Enrollment $enrollment, CarbonImmutable $monday, CarbonImmutable $today): PlanInput
    {
        $lesson = Lesson::forUser($user)
            ->where('enrollment_id', $enrollment->id)
            ->where('status', LessonStatus::Ready)
            ->latest('created_at')
            ->latest('id')
            ->first();

        return new PlanInput(
            weekStart: $monday,
            from: $today,
            goalMinutes: $enrollment->daily_goal_minutes,
            lessonId: $lesson?->id,
            lessonActivities: $lesson === null ? [] : $this->activities($user, $lesson),
            hasCards: Card::forUser($user)->whereNull('suspended_at')->exists(),
            hasMistakes: Mistake::forUser($user)->open()->exists(),
        );
    }

    /**
     * Atividades prontas da aula, na ordem do rodízio.
     *
     * @return list<LessonActivity>
     */
    private function activities(User $user, Lesson $lesson): array
    {
        $sessions = PracticeSession::forUser($user)
            ->where('source_lesson_id', $lesson->id)
            ->pluck('kind')
            ->all();

        $hasCards = Card::forUser($user)
            ->whereNull('suspended_at')
            ->where(fn (Builder $query) => $query
                ->where('source_lesson_id', $lesson->id)
                ->orWhereIn('id', Card::idsInLesson($lesson)))
            ->exists();

        return array_values(array_filter([
            in_array(SessionKind::Writing, $sessions, true) ? LessonActivity::Writing : null,
            in_array(SessionKind::Reading, $sessions, true) ? LessonActivity::Reading : null,
            $hasCards ? LessonActivity::Vocabulary : null,
        ]));
    }

    /**
     * @param  list<DayDraft>  $days
     */
    private function store(User $user, Enrollment $enrollment, CarbonImmutable $monday, array $days): void
    {
        foreach ($days as $draft) {
            $day = (new PlanDay)->forceFill([
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'date' => $draft->date,
                'week_start' => $monday->toDateString(),
                'goal_minutes' => $enrollment->daily_goal_minutes,
            ]);
            $day->save();

            foreach ($draft->tasks as $position => $task) {
                (new PlanTask)->forceFill([
                    'user_id' => $user->id,
                    'plan_day_id' => $day->id,
                    'position' => $position + 1,
                    'kind' => $task->kind,
                    'activity' => $task->activity,
                    'source_lesson_id' => $task->lessonId,
                    'minutes' => $task->minutes,
                    'important' => $task->important,
                ])->save();
            }
        }
    }
}
