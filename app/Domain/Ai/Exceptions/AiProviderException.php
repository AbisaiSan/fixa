<?php

namespace App\Domain\Ai\Exceptions;

use RuntimeException;

/**
 * Falha do provedor de IA. A mensagem nunca carrega texto do aluno (IAS-04).
 */
class AiProviderException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
