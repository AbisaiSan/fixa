<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estado de repetição espaçada do cartão (FLA-03, FLA-04).
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->decimal('ease', 4, 2)->default(2.5);
            $table->unsignedSmallInteger('lapses')->default(0);
            // Nulo enquanto o cartão é novo.
            $table->timestamp('due_at')->nullable();
            // Quando entrou na rotina, para o limite de novos por dia.
            $table->timestamp('first_reviewed_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            $table->index(['user_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'due_at']);
            $table->dropColumn(['repetitions', 'interval_days', 'ease', 'lapses', 'due_at', 'first_reviewed_at', 'last_reviewed_at']);
        });
    }
};
