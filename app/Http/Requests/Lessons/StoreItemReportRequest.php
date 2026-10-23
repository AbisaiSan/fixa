<?php

namespace App\Http\Requests\Lessons;

use App\Domain\Lessons\Reports\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Reportar erro" (MAT-05): qual item, o motivo e detalhes opcionais.
 */
class StoreItemReportRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'item' => ['required', 'string', 'max:60'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['item' => 'item', 'reason' => 'motivo', 'details' => 'detalhes'];
    }
}
