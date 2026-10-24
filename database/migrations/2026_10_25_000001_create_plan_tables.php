<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plano da semana (J5-B1, PLA-01): um dia por data do calendário do aluno (GAM-06).
        Schema::create('plan_days', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->date('week_start');
            $table->unsignedTinyInteger('goal_minutes');
            $table->timestamps();

            $table->unique(['enrollment_id', 'date']);
            $table->index(['user_id', 'week_start']);
        });

        // Os blocos do dia (PLA-02): só tipo, minutos e a aula de origem, sem conteúdo do aluno.
        Schema::create('plan_tasks', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('kind', 20);
            $table->string('activity', 20)->nullable();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->unsignedTinyInteger('minutes');
            $table->boolean('important')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['plan_day_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_tasks');
        Schema::dropIfExists('plan_days');
    }
};
