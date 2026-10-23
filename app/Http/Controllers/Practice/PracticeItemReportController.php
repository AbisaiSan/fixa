<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Lessons\Reports\ReportItem;
use App\Domain\Lessons\Reports\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\PracticeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * "Reportar correção" num item de prática (J4-F3, J4-F8, MAT-05).
 */
class PracticeItemReportController extends Controller
{
    public function store(Request $request, PracticeItem $item, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $reports->handle($item, PracticeItem::ITEM, ReportReason::from($validated['reason']), $validated['details'] ?? null);

        return back()->with('status', 'item-reported');
    }
}
