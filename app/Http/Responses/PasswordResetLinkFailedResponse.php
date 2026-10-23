<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * E-mail inexistente recebe a mesma resposta de sucesso, para não revelar
 * quem tem conta (ACE-02). Os demais erros, como excesso de pedidos, seguem normais.
 */
class PasswordResetLinkFailedResponse extends FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request): Response
    {
        if ($this->status === Password::INVALID_USER) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
