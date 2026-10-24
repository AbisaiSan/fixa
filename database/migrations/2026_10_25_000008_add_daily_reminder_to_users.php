<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lembrete diário (J5-B10): hora escolhida no fuso do aluno e o último dia enviado.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('daily_reminder_hour')->default(19);
            $table->date('daily_reminder_sent_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_reminder_hour', 'daily_reminder_sent_on']);
        });
    }
};
