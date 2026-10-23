<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cartões da aula (FLA-01). Termos e frases curtas, sem criptografia,
        // para permitir busca e deduplicação no banco (ADR 0003).
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('kind', 10);
            $table->string('front', 200);
            $table->string('back', 300);
            $table->string('example', 300)->nullable();
            $table->string('example_translation', 300)->nullable();
            // Quem gerou: provedor, modelo e versão do prompt. Nulos no cartão do aluno.
            $table->string('provider', 30)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('prompt_version', 10)->nullable();
            $table->boolean('reported')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_lesson_id']);
        });

        // Aulas em que o cartão aparece (FLA-05): a repetida só ganha a referência.
        Schema::create('card_lesson', function (Blueprint $table) {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['card_id', 'lesson_id']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->timestamp('cards_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('cards_generated_at');
        });
        Schema::dropIfExists('card_lesson');
        Schema::dropIfExists('cards');
    }
};
