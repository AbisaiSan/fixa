<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Plano da semana: roda de hora em hora para pegar a segunda-feira no fuso de cada aluno (J5-B1, GAM-06).
Schedule::command('fixa:plan-week')->hourly()->withoutOverlapping();
