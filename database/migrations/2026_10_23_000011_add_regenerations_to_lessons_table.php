<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Novas gerações pedidas pelo aluno, com limite (REV-04).
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedSmallInteger('regenerations')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('regenerations');
        });
    }
};
