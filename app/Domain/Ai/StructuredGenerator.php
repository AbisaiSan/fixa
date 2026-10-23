<?php

namespace App\Domain\Ai;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Contracts\OutputSchema;
use App\Domain\Ai\Exceptions\AiOutputInvalidException;
use App\Domain\Ai\Exceptions\AiProviderException;
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
        private AiUsage $usage,
    ) {}

    /**
     * @param  array<string, string>  $variables
     */
    public function generate(
        AiTask $task,
        OutputSchema $schema,
        string $material,
        array $variables = [],
        AiCallContext $context = new AiCallContext,
    ): AiResponse {
        $prompt = $this->prompts->build($task, $material, $variables);
        $request = new AiRequest(
            task: $task,
            promptVersion: $prompt->version,
            instructions: $prompt->instructions,
            material: $prompt->material,
            schema: $schema->providerSchema(),
        );

        $failed = [];

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $this->usage->ensureWithinBudget();

            try {
                $response = $this->provider->generate($request);
            } catch (AiProviderException $exception) {
                $this->usage->record($task, $prompt->version, null, 'error', $context, $this->provider->name());

                throw $exception;
            }

            $validator = Validator::make($response->data, $schema->rules());
            $this->usage->record($task, $prompt->version, $response, $validator->passes() ? 'ok' : 'invalid', $context, $this->provider->name());

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
