<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posições da lista "você consegue?" que o aluno marcou (J2-F4). Só números.
        Schema::table('lesson_reviews', function (Blueprint $table) {
            $table->json('can_do_done')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_reviews', function (Blueprint $table) {
            $table->dropColumn('can_do_done');
        });
    }
};
