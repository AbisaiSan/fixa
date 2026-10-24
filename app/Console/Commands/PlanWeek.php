<?php

namespace App\Console\Commands;

use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Plan\RecalculatePlan;
use App\Domain\Progress\SettleStreak;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fixa:plan-week')]
#[Description('Fecha a sequência, gera o plano da semana e refaz o dia de quem faltou, no fuso de cada aluno (J5-B1, J5-B2, J5-B6)')]
class PlanWeek extends Command
{
    public function handle(GenerateWeeklyPlan $generator, RecalculatePlan $recalculator, SettleStreak $streaks): int
    {
        $created = 0;
        $recalculated = 0;

        User::query()
            ->whereHas('activeEnrollment')
            ->lazyById(100)
            ->each(function (User $user) use ($generator, $recalculator, $streaks, &$created, &$recalculated) {
                // Fecha os dias que passaram antes de mexer no plano (GAM-02).
                $streaks->for($user);

                if ($generator->for($user)) {
                    $created++;
                }

                if ($recalculator->for($user)) {
                    $recalculated++;
                }
            });

        $this->info("Planos criados: {$created}. Dias recalculados: {$recalculated}.");

        return self::SUCCESS;
    }
}
