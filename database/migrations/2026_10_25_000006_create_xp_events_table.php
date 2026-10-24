<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de XP (J5-B7, GAM-03): só inserção, um evento por origem.
        // Sem chave estrangeira para a origem: apagar uma aula não apaga XP.
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->unsignedBigInteger('source_id');
            $table->unsignedSmallInteger('xp');
            $table->timestamp('earned_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'source', 'source_id']);
            $table->index(['user_id', 'earned_at']);
        });

        // O PostgreSQL não tem inteiro sem sinal: o XP de um evento nunca é negativo.
        DB::statement('ALTER TABLE xp_events ADD CONSTRAINT xp_events_xp_not_negative CHECK (xp >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
