<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Caderno de erros (J4-B8, ERR-01). Um registro por ponto errado do aluno.
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('category', 20);
            // Chave do "mesmo ponto" (ERR-03): o ponto normalizado, sem conteúdo do aluno.
            $table->string('point_key', 64);
            // Ponto, texto do aluno, correção e explicação, criptografados (DAD-03).
            $table->text('content');
            $table->unsignedSmallInteger('repetitions')->default(1);
            $table->boolean('priority')->default(false);
            $table->unsignedTinyInteger('step')->default(0);
            $table->unsignedTinyInteger('correct_streak')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_error_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category', 'point_key']);
            $table->index(['user_id', 'resolved_at', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};
