<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica a requisição e o aluno nos logs, sem dados pessoais (OPE-02).
 */
class AddRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = strtolower((string) Str::ulid());

        Context::add('request_id', $requestId);

        $user = $request->user();

        if ($user instanceof User) {
            Context::add('user', $user->ulid);
        }

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
