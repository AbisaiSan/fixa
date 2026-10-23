<?php

namespace App\Domain\Ai;

/**
 * A quem a chamada se refere, para custo por aluno (IAS-03). Só IDs.
 */
final readonly class AiCallContext
{
    public function __construct(
        public ?int $userId = null,
        public ?int $lessonId = null,
    ) {}
}
