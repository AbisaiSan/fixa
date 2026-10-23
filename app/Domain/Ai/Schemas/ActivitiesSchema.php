<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Ai\Rules\MaxWords;
use App\Domain\Practice\ItemContext;
use Illuminate\Validation\Rule;

/**
 * Sessão de escrita da aula (J4-B1, ATV-01): 5 a 8 itens de completar,
 * traduzir e formar frase, já com gabarito e explicação (ATV-02).
 */
final class ActivitiesSchema implements OutputSchema
{
    public const TYPES = ['fill', 'translate', 'context'];

    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'items' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING', 'enum' => self::TYPES],
                            'context' => ['type' => 'STRING', 'enum' => array_column(ItemContext::cases(), 'value')],
                            'instruction' => $string,
                            'sentence' => $string,
                            'accepted' => ['type' => 'ARRAY', 'items' => $string],
                            'corrected' => $string,
                            'natural' => $string,
                            'why' => $string,
                            'point' => $string,
                        ],
                        'required' => ['type', 'context', 'instruction', 'sentence', 'accepted', 'corrected', 'natural', 'why', 'point'],
                    ],
                ],
            ],
            'required' => ['items'],
        ];
    }

    public function rules(): array
    {
        $text = ['required', 'string', 'max:300'];

        return [
            'items' => ['required', 'array', 'min:5', 'max:8'],
            'items.*.type' => ['required', Rule::in(self::TYPES)],
            'items.*.context' => ['required', Rule::enum(ItemContext::class)],
            'items.*.instruction' => ['required', 'string', 'max:200'],
            'items.*.sentence' => $text,
            // Completar e traduzir precisam de gabarito para corrigir sem IA (ATV-02).
            'items.*.accepted' => ['present', 'array', 'max:20', 'required_unless:items.*.type,context'],
            'items.*.accepted.*' => ['required', 'string', 'max:300'],
            'items.*.corrected' => $text,
            'items.*.natural' => $text,
            'items.*.why' => ['required', 'string', 'max:500', new MaxWords(45)],
            'items.*.point' => ['required', 'string', 'max:80'],
        ];
    }
}
