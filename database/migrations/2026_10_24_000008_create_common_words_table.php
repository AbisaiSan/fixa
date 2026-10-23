<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Palavras mais comuns de cada idioma (J3-B10, VOC-04). Dado de
     * referência, igual para todos os alunos. Fica vazia até a carga da J7,
     * que vem de uma lista de frequência com licença verificada.
     */
    public function up(): void
    {
        Schema::create('common_words', function (Blueprint $table) {
            $table->id();
            $table->string('language', 10);
            $table->string('term', 120);
            // Posição na lista de frequência: 1 é a mais comum.
            $table->unsignedInteger('rank');
            $table->string('part_of_speech', 30)->nullable();
            // Lista de origem e licença, para conferir a procedência.
            $table->string('source', 120);
            $table->timestamps();

            $table->unique(['language', 'term']);
            $table->index(['language', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_words');
    }
};
