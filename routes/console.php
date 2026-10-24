<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Plano da semana e recálculo depois da falta: de hora em hora, para pegar a virada do dia no fuso de cada aluno (J5-B1, J5-B2, GAM-06).
Schedule::command('fixa:plan-week')->hourly()->withoutOverlapping();

// Lembrete diário por e-mail, na hora escolhida no fuso de cada aluno (J5-B10).
Schedule::command('fixa:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
