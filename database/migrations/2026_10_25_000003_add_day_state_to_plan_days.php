<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estados do dia (J5-B4): contou para a sequência ou foi protegido por congelamento.
        Schema::table('plan_days', function (Blueprint $table) {
            $table->timestamp('counted_at')->nullable();
            $table->timestamp('frozen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plan_days', function (Blueprint $table) {
            $table->dropColumn(['counted_at', 'frozen_at']);
        });
    }
};
