<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Um cartão pode perder a aula de origem e continuar reportável (MAT-05).
        Schema::table('item_reports', function (Blueprint $table) {
            $table->foreignId('source_lesson_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_reports', function (Blueprint $table) {
            $table->foreignId('source_lesson_id')->nullable(false)->change();
        });
    }
};
