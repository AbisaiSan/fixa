<?php

namespace App\Domain\Ai\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Limita a quantidade de palavras de um texto gerado (REV-01: resumo de até 150).
 */
final readonly class MaxWords implements ValidationRule
{
    public function __construct(private int $max) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && str_word_count($value, 0, 'ÀÁÂÃÇÉÊÍÓÔÕÚàáâãçéêíóôõú') > $this->max) {
            $fail("The {$attribute} has more than {$this->max} words.");
        }
    }
}
