<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document', 20);
            $table->string('version', 20);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();

            $table->unique(['user_id', 'document', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
