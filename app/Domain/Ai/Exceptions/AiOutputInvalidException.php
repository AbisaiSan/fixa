<?php

namespace App\Domain\Ai\Exceptions;

use RuntimeException;

/**
 * A IA respondeu fora do esquema mesmo depois de uma nova tentativa (IAS-02).
 * Guarda só os campos que falharam, nunca o conteúdo.
 */
class AiOutputInvalidException extends RuntimeException
{
    /**
     * @param  list<string>  $failedFields
     */
    public function __construct(public readonly array $failedFields)
    {
        parent::__construct('AI output did not match the schema: '.implode(', ', $failedFields));
    }
}
