<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Administrador sem 2FA ativo só acessa o necessário para ativá-lo (ACE-03).
 */
class RequireTwoFactorForAdmins
{
    /**
     * Rotas liberadas enquanto o 2FA não está ativo.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'security.edit',
        'two-factor.*',
        'password.confirm',
        'password.confirmation',
        'logout',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin() || $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            abort(403, 'Two-factor authentication required.');
        }

        return redirect()->route('security.edit')->with('status', 'two-factor-required');
    }
}
