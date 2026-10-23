<?php

namespace App\Domain\Ai;

use App\Models\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Limite diário de pedidos de IA feitos pelo aluno (ATV-05). Conta as
 * chamadas das tarefas pedidas por ele desde a meia-noite no fuso dele.
 * Ao chegar no limite, só a IA para: flashcards e o resto continuam.
 */
final class DailyAiAllowance
{
    public function used(User $user): int
    {
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;
        $tasks = array_map(fn (AiTask $task) => $task->value, array_values(array_filter(AiTask::cases(), fn (AiTask $task) => $task->countsTowardsDailyLimit())));

        return DB::table('ai_calls')
            ->where('user_id', $user->id)
            ->whereIn('task', $tasks)
            ->where('created_at', '>=', CarbonImmutable::now($timezone)->startOfDay()->utc())
            ->count();
    }

    public function left(User $user): int
    {
        return max(0, $this->limit() - $this->used($user));
    }

    /**
     * Contador para a tela (J4-B5): usados, limite, restantes e quando volta.
     *
     * @return array{used: int, limit: int, left: int, reached: bool, resets_at: string}
     */
    public function summary(User $user): array
    {
        $used = $this->used($user);
        $limit = $this->limit();
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;

        return [
            'used' => $used,
            'limit' => $limit,
            'left' => max(0, $limit - $used),
            'reached' => $used >= $limit,
            'resets_at' => CarbonImmutable::now($timezone)->addDay()->startOfDay()->toIso8601String(),
        ];
    }

    public function limit(): int
    {
        return (int) config('fixa.ai.daily_student_limit', 30);
    }
}
