<?php

use App\Domain\Lessons\LessonStatus;
use App\Domain\Plan\GenerateWeeklyPlan;
use App\Domain\Practice\SessionKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\PlanDay;
use App\Models\PlanTask;
use App\Models\PracticeSession;
use App\Models\Streak;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

/**
 * Testes de tempo (J5-B11, PLA-03, GAM-06): relógio congelado, fusos
 * diferentes e virada de dia, rodando o comando de hora em hora.
 */
beforeEach(function () {
    $this->student = function (string $timezone): User {
        $user = User::factory()->create();
        $user->activeEnrollment->forceFill(['timezone' => $timezone])->save();
        $lesson = Lesson::factory()->forUser($user)->status(LessonStatus::Ready)->create();
        Card::factory()->forLesson($lesson)->create();
        (new PracticeSession)->forceFill(['user_id' => $user->id, 'source_lesson_id' => $lesson->id, 'kind' => SessionKind::Writing])->save();

        return $user->fresh();
    };
    $this->localToday = fn (User $user): string => CarbonImmutable::now($user->activeEnrollment->timezone)->toDateString();
});

test('48 hourly runs recalculate and settle each local day exactly once, in every timezone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 00:30:00', 'UTC'));
    $students = collect(['America/Rio_Branco', 'America/Sao_Paulo', 'America/Noronha', 'Europe/Lisbon'])
        ->mapWithKeys(fn (string $timezone) => [$timezone => ($this->student)($timezone)]);
    // Todos estudam todo dia: a sequência tem de acompanhar os dias fechados.
    $studyToday = function () use ($students): void {
        foreach ($students as $user) {
            PlanDay::forUser($user)->whereDate('date', ($this->localToday)($user))->update(['counted_at' => now()]);
        }
    };

    foreach (range(1, 48) as $hour) {
        $this->artisan('fixa:plan-week')->assertSuccessful();
        $studyToday();

        foreach ($students as $timezone => $user) {
            $today = ($this->localToday)($user);
            $yesterday = CarbonImmutable::parse($today)->subDay()->toDateString();
            $streak = Streak::forUser($user)->sole();
            $planned = PlanDay::forUser($user)->whereDate('date', '<=', $today)->whereDate('week_start', '2026-10-26')->get();

            expect($streak->settled_through->toDateString())->toBe($yesterday, "{$timezone} às {$hour}h")
                ->and($planned->whereNull('recalculated_at')->count())->toBe(0, "{$timezone} às {$hour}h");
        }

        $this->travel(1)->hour();
    }

    // Cada dia fechado desde a matrícula, no fuso do aluno, somou 1 na sequência.
    // A última rodada foi uma hora antes do relógio atual.
    foreach ($students as $timezone => $user) {
        $first = CarbonImmutable::parse('2026-10-26 00:30:00', 'UTC')->setTimezone($timezone)->startOfDay();
        $closed = (int) $first->diffInDays(CarbonImmutable::now($timezone)->subHour()->startOfDay());
        expect(Streak::forUser($user)->sole()->current)->toBe($closed, $timezone);
    }
});

test('a missed day never moves twice, even with the command running every hour', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)('America/Sao_Paulo');
    app(GenerateWeeklyPlan::class)->for($user);
    // Faltou segunda; o comando roda de hora em hora pela terça inteira.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 03:00:00', 'UTC'));

    foreach (range(1, 24) as $hour) {
        $this->artisan('fixa:plan-week')->assertSuccessful();
        $this->travel(1)->hour();
    }

    expect(PlanTask::forUser($user)->whereNotNull('moved_from')->count())->toBe(1);
});

test('the end of daylight saving time in Lisbon keeps a 7 day week and turns the week at local midnight', function () {
    // Domingo, 25/10/2026: Lisboa volta de UTC+1 para UTC+0 à 1h UTC.
    $this->travelTo(CarbonImmutable::parse('2026-10-25 12:00:00', 'UTC'));
    $user = ($this->student)('Europe/Lisbon');
    app(GenerateWeeklyPlan::class)->for($user);
    expect(PlanDay::forUser($user)->pluck('date')->map->toDateString()->all())->toBe(['2026-10-25']);

    // 23h59 de domingo em Lisboa (já UTC+0): ainda a semana de 19/10.
    $this->travelTo(CarbonImmutable::parse('2026-10-25 23:59:00', 'UTC'));
    expect(app(GenerateWeeklyPlan::class)->for($user))->toBeFalse();

    // 0h de segunda em Lisboa.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 00:00:00', 'UTC'));
    expect(app(GenerateWeeklyPlan::class)->for($user))->toBeTrue()
        ->and(PlanDay::forUser($user)->whereDate('week_start', '2026-10-26')->count())->toBe(7);
});

test('changing timezone in the middle of the week neither duplicates nor skips days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)('America/Sao_Paulo');
    $this->artisan('fixa:plan-week')->assertSuccessful();
    PlanDay::forUser($user)->whereDate('date', '2026-10-26')->update(['counted_at' => now()]);

    // Na terça, 1h UTC, ainda segunda em São Paulo; o aluno muda para Lisboa, onde já é terça.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 01:00:00', 'UTC'));
    $user->activeEnrollment->forceFill(['timezone' => 'Europe/Lisbon'])->save();
    $user = $user->fresh();
    $this->artisan('fixa:plan-week')->assertSuccessful();

    expect(PlanDay::forUser($user)->count())->toBe(7)
        ->and(PlanDay::forUser($user)->distinct()->count('date'))->toBe(7)
        ->and(Streak::forUser($user)->sole()->settled_through->toDateString())->toBe('2026-10-26')
        ->and(Streak::forUser($user)->sole()->current)->toBe(1);
});

test('the daily reminder turns over with the student day, not with UTC', function () {
    Mail::fake();
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    $user = ($this->student)('America/Sao_Paulo');
    $user->forceFill(['notify_daily_reminder' => true, 'daily_reminder_hour' => 22])->save();

    // 22h em São Paulo é 1h UTC do dia seguinte: o lembrete é do dia 26 local.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 01:00:00', 'UTC'));
    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 1.');
    expect($user->fresh()->daily_reminder_sent_on?->toDateString())->toBe('2026-10-26');

    // 2h59 UTC ainda é dia 26 em São Paulo: nada de segundo lembrete.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 02:59:00', 'UTC'));
    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 0.');
});
