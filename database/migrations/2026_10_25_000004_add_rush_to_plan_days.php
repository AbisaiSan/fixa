<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modo corrida do dia (J5-B5, PLA-04): início pelo servidor e conclusão.
        Schema::table('plan_days', function (Blueprint $table) {
            $table->timestamp('rush_started_at')->nullable();
            $table->timestamp('rush_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plan_days', function (Blueprint $table) {
            $table->dropColumn(['rush_started_at', 'rush_completed_at']);
        });
    }
};
