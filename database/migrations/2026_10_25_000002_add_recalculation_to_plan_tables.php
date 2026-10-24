<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recálculo depois da falta (J5-B2, PLA-03): uma vez por aluno e por dia.
        Schema::table('plan_days', function (Blueprint $table) {
            $table->timestamp('recalculated_at')->nullable();
        });

        Schema::table('plan_tasks', function (Blueprint $table) {
            // Na tarefa atrasada: já foi remanejada ou descartada.
            $table->timestamp('rescheduled_at')->nullable();
            // Na cópia remanejada: o dia de onde veio.
            $table->date('moved_from')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plan_tasks', function (Blueprint $table) {
            $table->dropColumn(['rescheduled_at', 'moved_from']);
        });

        Schema::table('plan_days', function (Blueprint $table) {
            $table->dropColumn('recalculated_at');
        });
    }
};
