<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Texto de leitura e glossário (J4-B6), criptografados como a revisão (DAD-03).
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->longText('content')->nullable();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->timestamp('reading_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('reading_generated_at');
        });

        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
