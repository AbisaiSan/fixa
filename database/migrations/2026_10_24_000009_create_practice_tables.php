<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sessões de prática (J4-B1, J4-B11, ATV-01): escrita da aula, leitura e ponto fraco.
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lesson_id')->nullable()->constrained('lessons')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20)->default('ready');
            $table->string('provider', 30)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('prompt_version', 10)->nullable();
            $table->boolean('reported')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind', 'status']);
        });

        Schema::create('practice_items', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('type', 20);
            // Dia a dia, trabalho ou viagem (ATV-01).
            $table->string('context', 20)->nullable();
            // Enunciado, frase, gabarito e explicação: gerados do material,
            // criptografados como a revisão (DAD-03).
            $table->text('content');
            $table->boolean('reported')->default(false);
            $table->timestamps();

            $table->unique(['practice_session_id', 'position']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->timestamp('activities_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('activities_generated_at');
        });
        Schema::dropIfExists('practice_items');
        Schema::dropIfExists('practice_sessions');
    }
};
