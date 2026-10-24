<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sequência e congelamentos (J5-B6, GAM-01, GAM-02), uma linha por matrícula.
        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current')->default(0);
            $table->unsignedInteger('longest')->default(0);
            $table->unsignedTinyInteger('freezes')->default(1);
            // Último dia fechado e a segunda da última semana que deu congelamento.
            $table->date('settled_through');
            $table->date('last_grant_week');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaks');
    }
};
