<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logado, vale a preferência da conta; senão, o cookie do navegador.
        $appearance = $request->user() instanceof User ? $request->user()->theme : $request->cookie('appearance');

        View::share('appearance', $appearance ?? 'system');

        return $next($request);
    }
}
