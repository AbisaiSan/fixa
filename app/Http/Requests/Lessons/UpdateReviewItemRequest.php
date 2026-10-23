<?php

namespace App\Http\Requests\Lessons;

use App\Domain\Lessons\WordCounter;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edição de um item da revisão (MAT-05), com os mesmos limites da geração
 * (REV-01: resumo de até 150 palavras).
 */
class UpdateReviewItemRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'summary' => ['sometimes', 'required', 'string', 'max:1500', $this->maxWords(150)],
            'explanation' => ['sometimes', 'required', 'string', 'max:700', $this->maxWords(120)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['summary' => 'resumo', 'explanation' => 'explicação'];
    }

    private function maxWords(int $max): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($max): void {
            if (is_string($value) && WordCounter::count($value) > $max) {
                $fail("Use até {$max} palavras.");
            }
        };
    }
}
