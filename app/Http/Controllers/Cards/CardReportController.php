<?php

namespace App\Http\Controllers\Cards;

use App\Domain\Lessons\Reports\ReportItem;
use App\Domain\Lessons\Reports\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\ItemReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Reportar erro" no cartão (MAT-05): sai das sessões até ser revisado.
 */
class CardReportController extends Controller
{
    public function store(Request $request, Card $card, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $card);

        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $reports->handle($card, Card::ITEM, ReportReason::from($validated['reason']), $validated['details'] ?? null);

        return back()->with('status', 'item-reported');
    }

    public function destroy(Card $card, ItemReport $report, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $card);
        Gate::authorize('update', $report);

        abort_unless($report->reportable_type === $card->getMorphClass() && $report->reportable_id === $card->id, 404);

        $reports->withdraw($report);

        return back()->with('status', 'report-withdrawn');
    }
}
