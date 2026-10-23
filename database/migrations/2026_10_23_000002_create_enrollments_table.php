<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target_language', 10);
            $table->string('instruction_language', 10)->default('pt-BR');
            $table->string('level', 10)->nullable();
            $table->unsignedSmallInteger('daily_goal_minutes')->default(10);
            $table->string('timezone', 64)->default('America/Sao_Paulo');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'target_language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
