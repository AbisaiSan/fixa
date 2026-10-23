<?php

namespace App\Http\Controllers\Lessons;

use App\Domain\Lessons\Reports\ReportItem;
use App\Domain\Lessons\Reports\ReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lessons\StoreItemReportRequest;
use App\Models\ItemReport;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * "Reportar erro" e "Desfazer" nos itens gerados da aula (MAT-05).
 */
class ItemReportController extends Controller
{
    public function store(StoreItemReportRequest $request, Lesson $lesson, ReportItem $report): RedirectResponse
    {
        Gate::authorize('update', $lesson);

        $review = $lesson->review;
        $item = $request->string('item')->value();

        abort_if($review === null, 404);

        if (! in_array($item, $review->itemKeys(), true)) {
            throw ValidationException::withMessages(['item' => 'Este item não existe mais na revisão.']);
        }

        $report->handle(
            $review,
            $item,
            ReportReason::from($request->string('reason')->value()),
            $request->filled('details') ? $request->string('details')->value() : null,
        );

        return back()->with('status', 'item-reported');
    }

    public function destroy(Lesson $lesson, ItemReport $report, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $lesson);
        Gate::authorize('update', $report);

        $reports->withdraw($report);

        return back()->with('status', 'report-withdrawn');
    }
}
