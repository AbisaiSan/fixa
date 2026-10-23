<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resposta do aluno a um item (J4-B4, J4-B11): resultado, correção e XP.
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_item_id')->constrained()->cascadeOnDelete();
            // Texto do aluno e correção, criptografados (DAD-03, IAS-04).
            $table->text('answer');
            $table->text('correction');
            $table->string('verdict', 10);
            // key: gabarito; ai: correção por IA.
            $table->string('source', 10);
            $table->unsignedSmallInteger('xp');
            $table->timestamp('answered_at');
            $table->timestamps();

            // Uma resposta por item: reenviar devolve a mesma correção.
            $table->unique('practice_item_id');
            $table->index(['user_id', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
