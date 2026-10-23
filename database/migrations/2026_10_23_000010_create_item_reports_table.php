<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Reportar erro" em item gerado (MAT-05). O item sai da rotina enquanto o report está aberto.
        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_lesson_id')->constrained('lessons')->cascadeOnDelete();
            // Item reportado: o registro gerado (revisão, depois cartões) e a parte dele.
            $table->morphs('reportable');
            $table->string('item_key', 60);
            $table->string('reason', 30);
            // Detalhes escritos pelo aluno, criptografados (DAD-03).
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_reports');
    }
};
