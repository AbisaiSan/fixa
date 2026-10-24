<?php

use App\Domain\Plan\GenerateWeeklyPlan;
use App\Mail\DailyReminder;
use App\Models\PlanDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Mail::fake();
    $this->user = User::factory()->create(['name' => 'Marina Souza']);
    $this->user->forceFill(['notify_daily_reminder' => true, 'daily_reminder_hour' => 19])->save();
});

test('the reminder goes at the chosen hour in the student timezone, once a day', function () {
    // 21h59 UTC = 18h59 em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 21:59:00', 'UTC'));
    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 0.');

    $this->travelTo(CarbonImmutable::parse('2026-10-26 22:00:00', 'UTC'));
    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 1.');
    $this->travel(15)->minutes();
    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 0.');

    Mail::assertQueued(DailyReminder::class, 1);
    expect($this->user->fresh()->daily_reminder_sent_on?->toDateString())->toBe('2026-10-26');
});

test('a student in another timezone gets it at their own 19h', function () {
    $this->user->activeEnrollment->forceFill(['timezone' => 'Europe/Lisbon'])->save();
    // 19h em Lisboa (UTC+0 depois de 25/10) e 16h em São Paulo.
    $this->travelTo(CarbonImmutable::parse('2026-10-26 19:00:00', 'UTC'));

    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 1.');
});

test('nobody who met the goal today or turned the reminder off gets it', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
    app(GenerateWeeklyPlan::class)->for($this->user);
    PlanDay::forUser($this->user)->whereDate('date', '2026-10-26')->update(['counted_at' => now()]);
    $off = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-10-26 23:00:00', 'UTC'));

    $this->artisan('fixa:send-reminders')->expectsOutput('Lembretes enviados: 0.');
    Mail::assertNothingQueued();
    expect($off->fresh()->notify_daily_reminder)->toBeFalse();
});

test('the email carries only the first name, the goal and the links', function () {
    $mail = new DailyReminder($this->user, 10);

    $mail->assertSeeInHtml('Oi, Marina')
        ->assertDontSeeInHtml('Souza')
        ->assertSeeInHtml('10 minutos')
        ->assertSeeInHtml(route('today'))
        ->assertSeeInHtml('lembrete/cancelar/'.$this->user->ulid);
    expect($mail->envelope()->subject)->toBe('Seus 10 minutos de hoje');
});

test('the signed link asks before turning the reminder off, without login', function () {
    $url = URL::signedRoute('reminders.unsubscribe', ['user' => $this->user->ulid]);

    $this->get($url)->assertOk()->assertSee('Parar o lembrete diário?');
    expect($this->user->fresh()->notify_daily_reminder)->toBeTrue();

    $this->post($url)->assertOk()->assertSee('Lembrete desativado');
    expect($this->user->fresh()->notify_daily_reminder)->toBeFalse();
});

test('a link without a valid signature changes nothing', function () {
    $this->get(route('reminders.unsubscribe', ['user' => $this->user->ulid]))->assertForbidden();
    $this->post(route('reminders.unsubscribe.confirm', ['user' => $this->user->ulid]))->assertForbidden();

    $other = User::factory()->create();
    $tampered = str_replace($this->user->ulid, $other->ulid, URL::signedRoute('reminders.unsubscribe', ['user' => $this->user->ulid]));
    $this->post($tampered)->assertForbidden();

    expect($this->user->fresh()->notify_daily_reminder)->toBeTrue();
});

test('the reminder hour is saved in settings within 6h to 22h', function () {
    $this->actingAs($this->user);
    $payload = ['theme' => 'system', 'notify_daily_reminder' => true, 'notify_weekly_summary' => false, 'notify_streak' => false];

    $this->patch(route('preferences.account'), [...$payload, 'daily_reminder_hour' => 7])->assertRedirect();
    expect($this->user->fresh()->daily_reminder_hour)->toBe(7);

    $this->patch(route('preferences.account'), [...$payload, 'daily_reminder_hour' => 3])->assertSessionHasErrors('daily_reminder_hour');
    expect($this->user->fresh()->daily_reminder_hour)->toBe(7);
});

test('the reminder runs every fifteen minutes', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'fixa:send-reminders'));

    expect($event?->expression)->toBe('*/15 * * * *');
});
