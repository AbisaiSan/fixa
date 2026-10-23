<?php

namespace App\Domain\Ai\Schemas;

use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Lessons\WordCounter;
use Closure;

/**
 * Texto de leitura da aula (J4-B6, ATV-04): 80 a 200 palavras no nível do
 * aluno, glossário para salvar palavras e 3 a 5 perguntas de escolha.
 */
final class ReadingSchema implements OutputSchema
{
    public const MIN_WORDS = 80;

    public const MAX_WORDS = 200;

    public function providerSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => $string,
                'paragraphs' => ['type' => 'ARRAY', 'items' => $string],
                'glossary' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['form' => $string, 'term' => $string, 'part_of_speech' => $string, 'translation' => $string],
                        'required' => ['form', 'term', 'part_of_speech', 'translation'],
                    ],
                ],
                'questions' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'question' => $string,
                            'options' => ['type' => 'ARRAY', 'items' => $string],
                            'answer' => ['type' => 'INTEGER'],
                        ],
                        'required' => ['question', 'options', 'answer'],
                    ],
                ],
            ],
            'required' => ['title', 'paragraphs', 'glossary', 'questions'],
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'paragraphs' => ['required', 'array', 'min:2', 'max:5', $this->wordRange()],
            'paragraphs.*' => ['required', 'string', 'max:1200'],
            'glossary' => ['required', 'array', 'min:5', 'max:15'],
            'glossary.*.form' => ['required', 'string', 'max:60'],
            'glossary.*.term' => ['required', 'string', 'max:120'],
            'glossary.*.part_of_speech' => ['required', 'string', 'max:30'],
            'glossary.*.translation' => ['required', 'string', 'max:200'],
            'questions' => ['required', 'array', 'min:3', 'max:5'],
            'questions.*.question' => ['required', 'string', 'max:200'],
            'questions.*.options' => ['required', 'array', 'size:3'],
            'questions.*.options.*' => ['required', 'string', 'max:120'],
            'questions.*.answer' => ['required', 'integer', 'min:0', 'max:2'],
        ];
    }

    private function wordRange(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $words = is_array($value) ? WordCounter::count(implode(' ', array_filter($value, 'is_string'))) : 0;

            if ($words < self::MIN_WORDS || $words > self::MAX_WORDS) {
                $fail("The reading text must have between 80 and 200 words, it has {$words}.");
            }
        };
    }
}
