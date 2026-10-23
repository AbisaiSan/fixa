<?php

namespace App\Domain\Lessons\Reports;

use App\Models\ItemReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * "Reportar erro" num item gerado (MAT-05): o item sai da rotina e fica
 * "em análise" até ser revisado ou até o aluno desfazer.
 */
final class ReportItem
{
    public function handle(Reportable&Model $reportable, string $item, ReportReason $reason, ?string $details): ItemReport
    {
        if (! in_array($item, $reportable->itemKeys(), true)) {
            throw new InvalidArgumentException('Unknown reportable item.');
        }

        return DB::transaction(function () use ($reportable, $item, $reason, $details) {
            // Reportar de novo o mesmo item não abre um segundo report.
            $report = $reportable->reports()->open()->where('item_key', $item)->first() ?? new ItemReport;

            $report->forceFill([
                'user_id' => $reportable->getAttribute('user_id'),
                'source_lesson_id' => $reportable->sourceLessonId(),
                'reportable_type' => $reportable->getMorphClass(),
                'reportable_id' => $reportable->getKey(),
                'item_key' => $item,
                'reason' => $reason,
                'details' => $details,
                'status' => ReportStatus::Open,
            ])->save();

            $reportable->refreshReported();

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

            $reportable = $report->reportable()->withoutGlobalScopes()->first();

            if ($reportable instanceof Reportable) {
                $reportable->refreshReported();
            }
        });
    }
}
