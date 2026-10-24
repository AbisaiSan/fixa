<?php

namespace App\Domain\Progress;

use App\Models\PlanDay;
use App\Models\Streak;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Fecha os dias que passaram (J5-B6, GAM-01, GAM-02): soma a sequência,
 * dá o congelamento da semana e aplica congelamento sozinho no dia perdido,
 * marcando o dia como protegido. Uma vez por dia no fuso do aluno (GAM-06).
 * Funciona sem aluno logado (comando de hora em hora).
 */
final class SettleStreak
{
    public function for(User $user, ?CarbonImmutable $now = null): ?Streak
    {
        $enrollment = $user->activeEnrollment;

        if ($enrollment === null) {
            return null;
        }

        $now ??= CarbonImmutable::now();
        $yesterday = $now->setTimezone($enrollment->timezone)->subDay()->toDateString();

        return DB::transaction(function () use ($user, $enrollment, $yesterday, $now) {
            $streak = Streak::forUser($user)->where('enrollment_id', $enrollment->id)->lockForUpdate()->first();

            if ($streak === null) {
                $first = StreakState::start(CarbonImmutable::instance($enrollment->created_at ?? $now)->setTimezone($enrollment->timezone)->toDateString());
                $streak = (new Streak)->forceFill([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'current' => $first->current,
                    'longest' => $first->longest,
                    'freezes' => $first->freezes,
                    'settled_through' => $first->settledThrough,
                    'last_grant_week' => $first->lastGrantWeek,
                ]);
                $streak->save();
            }

            $from = $streak->settled_through->copy()->addDay()->toDateString();

            if ($from > $yesterday) {
                return $streak;
            }

            $counted = PlanDay::forUser($user)
                ->where('enrollment_id', $enrollment->id)
                ->whereBetween('date', [$from, $yesterday])
                ->whereNotNull('counted_at')
                ->pluck('date')
                ->map(fn ($date) => $date->toDateString())
                ->all();

            // Dia sem plano também fecha, como não estudado.
            $days = [];
            foreach (CarbonPeriod::create($from, $yesterday) as $date) {
                $days[$date->toDateString()] = in_array($date->toDateString(), $counted, true);
            }

            $result = StreakRules::settle($streak->state(), $days);

            $streak->forceFill([
                'current' => $result->state->current,
                'longest' => $result->state->longest,
                'freezes' => $result->state->freezes,
                'settled_through' => $result->state->settledThrough,
                'last_grant_week' => $result->state->lastGrantWeek,
            ])->save();

            if ($result->frozen !== []) {
                PlanDay::forUser($user)
                    ->where('enrollment_id', $enrollment->id)
                    ->whereIn('date', $result->frozen)
                    ->update(['frozen_at' => $now]);
            }

            return $streak;
        });
    }
}
