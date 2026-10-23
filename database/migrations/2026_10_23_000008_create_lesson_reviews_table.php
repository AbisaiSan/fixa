<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_reviews', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            // Conteúdo gerado a partir do material, criptografado (DAD-03).
            $table->text('summary');
            $table->longText('concepts');
            $table->longText('common_mistakes');
            $table->text('can_do');
            $table->string('provider', 30);
            $table->string('model', 60);
            $table->string('prompt_version', 10);
            $table->boolean('reported')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_reviews');
    }
};
