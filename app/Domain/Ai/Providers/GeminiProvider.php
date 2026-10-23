<?php

namespace App\Domain\Ai\Providers;

use App\Domain\Ai\AiRequest;
use App\Domain\Ai\AiResponse;
use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\ModelTier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use JsonException;

/**
 * Adaptador do Gemini pela API REST generateContent (ADR 0002).
 *
 * Sem ferramentas, sem histórico: cada chamada leva só as instruções do
 * prompt, o material delimitado e o esquema de saída.
 */
final class GeminiProvider implements AiProvider
{
    public function __construct(
        private Http $http,
        private string $apiKey,
        private string $baseUrl,
        private string $strongModel,
        private string $lightModel,
        private int $timeoutSeconds,
    ) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function generate(AiRequest $request): AiResponse
    {
        if ($this->apiKey === '') {
            throw new AiProviderException('Gemini API key is not configured.');
        }

        $model = $request->task->tier() === ModelTier::Light ? $this->lightModel : $this->strongModel;

        try {
            $response = $this->http
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->acceptJson()
                ->timeout($this->timeoutSeconds)
                ->post(rtrim($this->baseUrl, '/')."/models/{$model}:generateContent", $this->payload($request));
        } catch (ConnectionException) {
            throw new AiProviderException('Gemini could not be reached.', retryable: true);
        }

        if ($response->failed()) {
            throw new AiProviderException(
                "Gemini answered with HTTP {$response->status()}.",
                retryable: $response->status() === 429 || $response->serverError(),
            );
        }

        $finishReason = (string) $response->json('candidates.0.finishReason', 'UNKNOWN');

        if ($finishReason !== 'STOP') {
            throw new AiProviderException("Gemini stopped with reason {$finishReason}.");
        }

        $text = '';
        $parts = $response->json('candidates.0.content.parts', []);

        foreach (is_array($parts) ? $parts : [] as $part) {
            if (is_array($part) && is_string($part['text'] ?? null)) {
                $text .= $part['text'];
            }
        }

        try {
            $data = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiProviderException('Gemini did not return valid JSON.', retryable: true);
        }

        if (! is_array($data)) {
            throw new AiProviderException('Gemini did not return a JSON object.', retryable: true);
        }

        return new AiResponse(
            data: $data,
            provider: $this->name(),
            model: $model,
            inputTokens: (int) $response->json('usageMetadata.promptTokenCount', 0),
            outputTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0)
                + (int) $response->json('usageMetadata.thoughtsTokenCount', 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AiRequest $request): array
    {
        return [
            'systemInstruction' => ['parts' => [['text' => $request->instructions]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $request->material]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $request->schema,
                'temperature' => 0.3,
            ],
        ];
    }
}
