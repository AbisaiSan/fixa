<?php

namespace App\Domain\Ai;

/**
 * Resposta da IA já decodificada, com o uso de tokens para custo (IAS-03).
 */
final readonly class AiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $provider,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
    ) {}
}
