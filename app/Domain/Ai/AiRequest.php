<?php

namespace App\Domain\Ai;

/**
 * Pedido estruturado à IA. As instruções vêm do prompt versionado e o
 * material do aluno vai separado, sempre como dado (IAS-01).
 */
final readonly class AiRequest
{
    /**
     * @param  array<string, mixed>  $schema  Esquema da resposta no formato do provedor (OpenAPI).
     * @param  list<AiImage>  $images
     */
    public function __construct(
        public AiTask $task,
        public string $promptVersion,
        public string $instructions,
        public string $material,
        public array $schema,
        public array $images = [],
    ) {}
}
