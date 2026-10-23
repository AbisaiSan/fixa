<?php

namespace App\Domain\Ai\Prompts;

use App\Domain\Ai\AiTask;

/**
 * Prompt carregado de resources/prompts, já com as variáveis aplicadas.
 */
final readonly class Prompt
{
    public function __construct(
        public AiTask $task,
        public string $version,
        public string $instructions,
        public string $material,
    ) {}
}
