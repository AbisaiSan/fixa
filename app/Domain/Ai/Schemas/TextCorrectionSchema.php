<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Ai\Rules\MaxWords;
use App\Domain\Practice\ErrorCategory;
use Illuminate\Validation\Rule;

/**
 * Correção de uma resposta livre (J4-B3, ATV-02): erro apontado, categoria,
 * explicação em português, versão corrigida e alternativa mais natural.
 */
final class TextCorrectionSchema implements OutputSchema
{
    public const VERDICTS = ['total', 'partial', 'wrong'];

    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'verdict' => ['type' => 'STRING', 'enum' => self::VERDICTS],
                'error' => $string,
                // Vazio quando está certa; o servidor confere o valor contra a lista.
                'category' => $string,
                'explanation' => $string,
                'corrected' => $string,
                'natural' => $string,
                'point' => $string,
            ],
            'required' => ['verdict', 'error', 'category', 'explanation', 'corrected', 'natural', 'point'],
        ];
    }

    public function rules(): array
    {
        return [
            'verdict' => ['required', Rule::in(self::VERDICTS)],
            'error' => ['present', 'nullable', 'string', 'max:300', 'required_unless:verdict,total'],
            'category' => ['present', 'nullable', 'required_unless:verdict,total', Rule::enum(ErrorCategory::class)],
            'explanation' => ['required', 'string', 'max:600', new MaxWords(60)],
            'corrected' => ['required', 'string', 'max:500'],
            'natural' => ['required', 'string', 'max:500'],
            'point' => ['present', 'nullable', 'string', 'max:80'],
        ];
    }
}
