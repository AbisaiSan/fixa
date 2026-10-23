<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Ai\Rules\MaxWords;

/**
 * Revisão da aula (REV-01 a REV-04): resumo de até 150 palavras, conceitos
 * com 3 exemplos traduzidos, erros comuns e a lista "você consegue?".
 */
final class LessonReviewSchema implements OutputSchema
{
    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'summary' => $string,
                'concepts' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => $string,
                            'explanation' => $string,
                            'examples' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => ['target' => $string, 'translation' => $string],
                                    'required' => ['target', 'translation'],
                                ],
                            ],
                        ],
                        'required' => ['name', 'explanation', 'examples'],
                    ],
                ],
                'common_mistakes' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['wrong' => $string, 'right' => $string, 'why' => $string],
                        'required' => ['wrong', 'right', 'why'],
                    ],
                ],
                'can_do' => ['type' => 'ARRAY', 'items' => $string],
            ],
            'required' => ['summary', 'concepts', 'common_mistakes', 'can_do'],
        ];
    }

    public function rules(): array
    {
        $text = ['required', 'string', 'max:600'];

        return [
            'summary' => ['required', 'string', 'max:1500', new MaxWords(150)],
            'concepts' => ['required', 'array', 'min:1', 'max:6'],
            'concepts.*.name' => ['required', 'string', 'max:120'],
            'concepts.*.explanation' => ['required', 'string', 'max:700', new MaxWords(60)],
            'concepts.*.examples' => ['required', 'array', 'size:3'],
            'concepts.*.examples.*.target' => $text,
            'concepts.*.examples.*.translation' => $text,
            'common_mistakes' => ['required', 'array', 'min:1', 'max:5'],
            'common_mistakes.*.wrong' => $text,
            'common_mistakes.*.right' => $text,
            'common_mistakes.*.why' => $text,
            'can_do' => ['required', 'array', 'min:3', 'max:5'],
            'can_do.*' => ['required', 'string', 'max:200'],
        ];
    }
}
