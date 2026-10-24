<?php

namespace App\Console\Commands;

use App\Domain\Plan\GenerateWeeklyPlan;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fixa:plan-week')]
#[Description('Gera o plano da semana de quem ainda não tem, no fuso de cada aluno (J5-B1)')]
class PlanWeek extends Command
{
    public function handle(GenerateWeeklyPlan $generator): int
    {
        $created = 0;

        User::query()
            ->whereHas('activeEnrollment')
            ->lazyById(100)
            ->each(function (User $user) use ($generator, &$created) {
                if ($generator->for($user)) {
                    $created++;
                }
            });

        $this->info("Planos criados: {$created}.");

        return self::SUCCESS;
    }
}
