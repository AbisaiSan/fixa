<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 10)->default('system');
            $table->boolean('notify_daily_reminder')->default(false);
            $table->boolean('notify_weekly_summary')->default(false);
            $table->boolean('notify_streak')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'notify_daily_reminder', 'notify_weekly_summary', 'notify_streak']);
        });
    }
};
