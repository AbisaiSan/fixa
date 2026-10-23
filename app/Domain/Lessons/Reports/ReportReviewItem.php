<?php

namespace App\Domain\Lessons\Reports;

use App\Models\ItemReport;
use App\Models\LessonReview;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * "Reportar erro" num item da revisão (MAT-05): o item sai da rotina e fica
 * "em análise" até ser revisado ou até o aluno desfazer.
 */
final class ReportReviewItem
{
    public function handle(LessonReview $review, string $item, ReportReason $reason, ?string $details): ItemReport
    {
        if (! in_array($item, $review->itemKeys(), true)) {
            throw new InvalidArgumentException('Unknown review item.');
        }

        return DB::transaction(function () use ($review, $item, $reason, $details) {
            // Reportar de novo o mesmo item não abre um segundo report.
            $report = $review->reports()->open()->where('item_key', $item)->first() ?? new ItemReport;

            $report->forceFill([
                'user_id' => $review->user_id,
                'source_lesson_id' => $review->lesson_id,
                'reportable_type' => $review->getMorphClass(),
                'reportable_id' => $review->id,
                'item_key' => $item,
                'reason' => $reason,
                'details' => $details,
                'status' => ReportStatus::Open,
            ])->save();

            $review->refreshReported();

            return $report;
        });
    }

    /**
     * Desfazer: o item volta para a rotina.
     */
    public function withdraw(ItemReport $report): void
    {
        DB::transaction(function () use ($report) {
            if ($report->status === ReportStatus::Open) {
                $report->forceFill(['status' => ReportStatus::Withdrawn])->save();
            }

            $review = $report->reportable;

            if ($review instanceof LessonReview) {
                $review->refreshReported();
            }
        });
    }
}
