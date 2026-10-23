<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mesma frente com o mesmo significado é o mesmo cartão (FLA-05).
        Schema::table('cards', function (Blueprint $table) {
            $table->string('dedupe_key', 64)->nullable();
            $table->unique(['user_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
    }
};
