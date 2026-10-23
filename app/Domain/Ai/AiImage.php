<?php

namespace App\Domain\Ai;

/**
 * Imagem enviada à IA (foto do caderno, página escaneada).
 */
final readonly class AiImage
{
    public function __construct(
        public string $mimeType,
        public string $base64,
    ) {}
}
