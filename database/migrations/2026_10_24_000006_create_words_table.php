<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vocabulário do aluno (VOC-01). Texto comum para a busca (ADR 0003).
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            // "lesson" ou "mine" (adicionada por mim, VOC-03).
            $table->string('origin', 10)->default('lesson');
            $table->string('language', 10);
            $table->string('term', 120);
            $table->string('part_of_speech', 30)->nullable();
            $table->string('translation', 200);
            $table->string('definition', 300)->nullable();
            // Dois exemplos com tradução e até cinco colocações.
            $table->json('examples')->nullable();
            $table->json('collocations')->nullable();
            $table->string('state', 10)->default('new');
            $table->string('dedupe_key', 64);
            $table->string('provider', 30)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('prompt_version', 10)->nullable();
            $table->boolean('reported')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'dedupe_key']);
            $table->index(['user_id', 'state']);
        });

        Schema::create('lesson_word', function (Blueprint $table) {
            $table->foreignId('word_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['word_id', 'lesson_id']);
        });

        // O cartão da palavra mede o estado dela (VOC-02).
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('word_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('word_id');
        });
        Schema::dropIfExists('lesson_word');
        Schema::dropIfExists('words');
    }
};
