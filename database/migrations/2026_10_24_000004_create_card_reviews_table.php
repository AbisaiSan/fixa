<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada resposta do aluno a um cartão (J3-B5, FLA-03): base de
        // vocabulário, erros e métricas. Só números, nenhum texto.
        Schema::create('card_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('interval_before');
            $table->unsignedSmallInteger('interval_after');
            $table->decimal('ease_after', 4, 2);
            $table->boolean('was_new')->default(false);
            // Identificador gerado no aparelho: a mesma resposta reenviada
            // depois de ficar sem conexão não conta duas vezes (J3-F7).
            $table->uuid('client_review_id')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'client_review_id']);
            $table->index(['card_id', 'reviewed_at']);
            $table->index(['user_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_reviews');
    }
};
