<?php

namespace App\Console\Commands;

use App\Domain\Plan\ReminderRule;
use App\Mail\DailyReminder;
use App\Models\Enrollment;
use App\Models\PlanDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('fixa:send-reminders')]
#[Description('Manda o lembrete diário a quem ligou e ainda não cumpriu a meta, no fuso de cada aluno (J5-B10)')]
class SendDailyReminders extends Command
{
    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('notify_daily_reminder', true)
            ->whereNotNull('email_verified_at')
            ->whereHas('activeEnrollment')
            ->with('activeEnrollment')
            ->lazyById(100)
            ->each(function (User $user) use (&$sent) {
                /** @var Enrollment $enrollment */
                $enrollment = $user->activeEnrollment;
                $local = CarbonImmutable::now($enrollment->timezone);
                $counted = PlanDay::forUser($user)
                    ->where('enrollment_id', $enrollment->id)
                    ->whereDate('date', $local->toDateString())
                    ->whereNotNull('counted_at')
                    ->exists();

                if (! ReminderRule::due(true, $user->daily_reminder_hour, $local, $user->daily_reminder_sent_on?->toDateString(), $counted)) {
                    return;
                }

                // Marca antes de enfileirar: uma falha não vira dois e-mails.
                $user->forceFill(['daily_reminder_sent_on' => $local->toDateString()])->save();
                Mail::to($user)->queue(new DailyReminder($user, $enrollment->daily_goal_minutes));
                $sent++;
            });

        $this->info("Lembretes enviados: {$sent}.");

        return self::SUCCESS;
    }
}
