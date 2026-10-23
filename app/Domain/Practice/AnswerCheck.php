<?php

namespace App\Domain\Practice;

/**
 * Comparação de uma resposta com o gabarito (J4-B2).
 */
final readonly class AnswerCheck
{
    public function __construct(
        public Verdict $verdict,
        // Resposta aceita mais próxima, para mostrar no erro pequeno.
        public ?string $closest = null,
    ) {}
}
