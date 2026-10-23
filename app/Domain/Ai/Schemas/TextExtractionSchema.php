<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;

/**
 * Transcrição de fotos e páginas escaneadas (J2-B1, J2-B4).
 */
final class TextExtractionSchema implements OutputSchema
{
    public function providerSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'text' => ['type' => 'STRING'],
                'legible' => ['type' => 'BOOLEAN'],
            ],
            'required' => ['text', 'legible'],
        ];
    }

    public function rules(): array
    {
        return [
            'text' => ['present', 'nullable', 'string', 'max:200000'],
            'legible' => ['required', 'boolean'],
        ];
    }
}
