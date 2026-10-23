<?php

namespace App\Domain\Ai\Exceptions;

/**
 * O teto mensal de gasto com IA foi atingido (IAS-03). Não adianta tentar de novo.
 */
class AiBudgetExceeded extends AiProviderException
{
    public function __construct()
    {
        parent::__construct('Monthly AI budget reached.', retryable: false);
    }
}
