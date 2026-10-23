<?php

namespace App\Domain\Ai;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Prompts\PromptLibrary;
use Illuminate\Support\Facades\Validator;

/**
 * Gera saída estruturada e confiável (J2-B8, IAS-02): monta o prompt
 * versionado, chama o provedor, valida contra o esquema, tenta uma vez de
 * novo se vier inválida e, depois, falha com AiOutputInvalidException.
 *
 * Falhas temporárias do provedor (limite, servidor fora) sobem como
 * AiProviderException e são repetidas pela fila, com espera entre tentativas.
 */
final class StructuredGenerator
{
    public const MAX_ATTEMPTS = 2;

    public function __construct(
        private AiProvider $provider,
        private PromptLibrary $prompts,
    ) {}

    /**
     * @param  array<string, string>  $variables
     * @param  list<AiImage>  $images
     */
    public function generate(
        AiTask $task,
        OutputSchema $schema,
        string $material,
        array $variables = [],
        array $images = [],
    ): AiResponse {
        $prompt = $this->prompts->build($task, $material, $variables);
        $request = new AiRequest(
            task: $task,
            promptVersion: $prompt->version,
            instructions: $prompt->instructions,
            material: $prompt->material,
            schema: $schema->providerSchema(),
            images: $images,
        );

        $failed = [];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $response = $this->provider->generate($request);
            $validator = Validator::make($response->data, $schema->rules());

            if ($validator->passes()) {
                return new AiResponse(
                    data: $validator->validated(),
                    provider: $response->provider,
                    model: $response->model,
                    inputTokens: $response->inputTokens,
                    outputTokens: $response->outputTokens,
                );
            }

            $failed = array_keys($validator->failed());
        }

        throw new AiOutputInvalidException($failed);
    }
}
