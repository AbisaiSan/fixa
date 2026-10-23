<?php

namespace App\Domain\Ai\Providers;

use App\Domain\Ai\AiRequest;
use App\Domain\Ai\AiResponse;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Exceptions\AiProviderException;
use PHPUnit\Framework\Assert;

/**
 * Adaptador falso: os testes nunca chamam a IA de verdade (Qualidade e testes).
 * Também é o padrão local enquanto não há chave configurada.
 */
final class FakeAiProvider implements AiProvider
{
    /** @var array<string, list<array<string, mixed>|AiProviderException>> */
    private array $queued = [];

    /** @var list<AiRequest> */
    private array $sent = [];

    public function name(): string
    {
        return 'fake';
    }

    /**
     * Enfileira respostas (ou falhas) para a tarefa, na ordem em que serão usadas.
     *
     * @param  array<string, mixed>|AiProviderException  ...$responses
     */
    public function respondWith(AiTask $task, array|AiProviderException ...$responses): self
    {
        foreach ($responses as $response) {
            $this->queued[$task->value][] = $response;
        }

        return $this;
    }

    public function generate(AiRequest $request): AiResponse
    {
        $this->sent[] = $request;

        $next = array_shift($this->queued[$request->task->value]) ?? ['fake' => true];

        if ($next instanceof AiProviderException) {
            throw $next;
        }

        return new AiResponse(
            data: $next,
            provider: $this->name(),
            model: 'fake-model',
            inputTokens: (int) ceil(mb_strlen($request->instructions.$request->material) / 4),
            outputTokens: (int) ceil(mb_strlen((string) json_encode($next)) / 4),
        );
    }

    /**
     * @return list<AiRequest>
     */
    public function sent(?AiTask $task = null): array
    {
        return array_values(array_filter($this->sent, fn (AiRequest $request) => $task === null || $request->task === $task));
    }

    public function assertSentCount(int $count, ?AiTask $task = null): void
    {
        Assert::assertCount($count, $this->sent($task));
    }
}
