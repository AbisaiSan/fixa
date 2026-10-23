<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;

/**
 * Dados de uma palavra salva pelo aluno (J3-B8, VOC-01).
 */
final class WordDetailsSchema implements OutputSchema
{
    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'translation' => $string,
                'part_of_speech' => $string,
                'definition' => $string,
                'examples' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['target' => $string, 'translation' => $string],
                        'required' => ['target', 'translation'],
                    ],
                ],
                'collocations' => ['type' => 'ARRAY', 'items' => $string],
            ],
            'required' => ['translation', 'part_of_speech', 'definition', 'examples', 'collocations'],
        ];
    }

    public function rules(): array
    {
        $text = ['required', 'string', 'max:300'];

        return [
            'translation' => ['required', 'string', 'max:200'],
            'part_of_speech' => ['required', 'string', 'max:30'],
            'definition' => $text,
            'examples' => ['required', 'array', 'size:2'],
            'examples.*.target' => $text,
            'examples.*.translation' => $text,
            'collocations' => ['present', 'array', 'max:5'],
            'collocations.*' => ['string', 'max:80'],
        ];
    }
}
