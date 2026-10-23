<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->date('lesson_date');
            $table->string('language', 10);
            $table->string('source', 10);
            $table->string('status', 20)->default('queued')->index();
            // Código do motivo, nunca o conteúdo (IAS-04).
            $table->string('failure_reason', 40)->nullable();
            // Texto extraído do material, criptografado pela aplicação (DAD-03, MAT-06).
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->timestamp('rights_declared_at');
            $table->timestamps();

            $table->index(['user_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
