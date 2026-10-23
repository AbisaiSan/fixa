<?php

namespace App\Domain\Lessons;

use App\Domain\Lessons\Reports\ReportStatus;
use App\Models\LessonReview;
use Illuminate\Support\Facades\DB;

/**
 * Edição da revisão pelo aluno (MAT-05). A versão dele fica guardada até
 * uma nova geração, que a substitui.
 */
final class EditLessonReview
{
    public function updateSummary(LessonReview $review, string $summary): void
    {
        $review->forceFill(['summary' => $summary])->save();
    }

    public function updateConcept(LessonReview $review, string $key, string $explanation): void
    {
        $review->forceFill([
            'concepts' => array_map(
                fn (array $concept) => $concept['key'] === $key ? ['explanation' => $explanation] + $concept : $concept,
                $review->concepts,
            ),
        ])->save();
    }

    /**
     * O conceito sai da revisão e da rotina. Report aberto dele perde o objeto.
     */
    public function removeConcept(LessonReview $review, string $key): void
    {
        DB::transaction(function () use ($review, $key) {
            $review->forceFill([
                'concepts' => array_values(array_filter($review->concepts, fn (array $concept) => $concept['key'] !== $key)),
            ])->save();

            $review->reports()->open()
                ->where('item_key', LessonReview::conceptItem($key))
                ->update(['status' => ReportStatus::Superseded]);

            $review->refreshReported();
        });
    }
}
