<?php

namespace App\Domain\Ai\Contracts;

/**
 * Esquema de saída de uma tarefa de IA (IAS-02): o mesmo contrato vira o
 * responseSchema enviado ao provedor e as regras validadas no servidor.
 */
interface OutputSchema
{
    /**
     * Esquema no formato OpenAPI aceito pelo provedor.
     *
     * @return array<string, mixed>
     */
    public function providerSchema(): array;

    /**
     * Regras de validação do Laravel para a resposta decodificada.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;
}
