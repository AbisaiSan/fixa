<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica o limitador "auth-forms" ao cadastro e à recuperação de senha,
 * que o Fortify não limita por conta própria (ACE-05).
 */
class ThrottleAuthForms
{
    public function __construct(private ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('register.store', 'password.email', 'password.update')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, 'auth-forms');
    }
}
