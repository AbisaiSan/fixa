<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Cards\CardKind;
use Illuminate\Validation\Rule;

/**
 * Cartões da aula (FLA-01, FLA-02): até 30, entre palavras e frases-chave,
 * cada um com frente, verso e exemplo traduzido.
 */
final class CardsSchema implements OutputSchema
{
    public function __construct(private int $max = 30) {}

    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'cards' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'kind' => ['type' => 'STRING', 'enum' => array_column(CardKind::cases(), 'value')],
                            'front' => $string,
                            'back' => $string,
                            'example' => $string,
                            'example_translation' => $string,
                        ],
                        'required' => ['kind', 'front', 'back', 'example', 'example_translation'],
                    ],
                ],
            ],
            'required' => ['cards'],
        ];
    }

    public function rules(): array
    {
        $text = ['required', 'string', 'max:300'];

        return [
            'cards' => ['required', 'array', 'min:1', 'max:'.$this->max],
            'cards.*.kind' => ['required', Rule::enum(CardKind::class)],
            'cards.*.front' => ['required', 'string', 'max:200'],
            'cards.*.back' => $text,
            'cards.*.example' => $text,
            'cards.*.example_translation' => $text,
        ];
    }
}
