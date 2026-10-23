<?php

namespace App\Domain\Ai\Contracts;

use App\Domain\Ai\AiRequest;
use App\Domain\Ai\AiResponse;
use App\Domain\Ai\Exceptions\AiProviderException;

/**
 * Único ponto de contato com provedores de IA. Trocar de provedor é
 * configuração (FIXA_AI_PROVIDER), sem mexer no resto do produto.
 *
 * O provedor não recebe ferramentas nem dados de outros alunos (IAS-01).
 */
interface AiProvider
{
    public function name(): string;

    /**
     * @throws AiProviderException quando o provedor falha, recusa ou devolve algo que não é JSON.
     */
    public function generate(AiRequest $request): AiResponse;
}
