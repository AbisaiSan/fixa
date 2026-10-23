<?php

use App\Models\LessonReview;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Dá uma chave estável a cada conceito das revisões já geradas (MAT-05),
     * para reportar, editar e remover sem depender da posição.
     */
    public function up(): void
    {
        LessonReview::withoutGlobalScopes()->each(function (LessonReview $review): void {
            $review->forceFill(['concepts' => LessonReview::withConceptKeys($review->concepts)])->save();
        });
    }

    public function down(): void
    {
        //
    }
};
