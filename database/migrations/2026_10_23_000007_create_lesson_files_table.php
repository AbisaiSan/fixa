<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_files', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            // Caminho com nome aleatório no disco privado "lessons" (UPL-02).
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('pages')->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->char('sha256', 64);
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_files');
    }
};
