<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bloco sem nada a fazer hoje (fila vazia, nenhum erro de volta): não pesa na meta (J5-B9).
        Schema::table('plan_tasks', function (Blueprint $table) {
            $table->timestamp('skipped_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plan_tasks', function (Blueprint $table) {
            $table->dropColumn('skipped_at');
        });
    }
};
