<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem ainda não aceitou a versão vigente dos termos e da privacidade
 * só acessa os próprios documentos, o aceite e a saída (J1-B7, DAD-01).
 */
class EnsureCurrentLegalAccepted
{
    /** @var list<string> */
    private const ALLOWED_ROUTES = ['legal.*', 'logout', 'verification.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $request->routeIs(...self::ALLOWED_ROUTES) || $user->hasAcceptedCurrentLegal()) {
            return $next($request);
        }

        return redirect()->guest(route('legal.accept'));
    }
}
