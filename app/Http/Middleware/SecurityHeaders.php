<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança e CSP com nonce por requisição (OPE-01, DAD-03).
 */
class SecurityHeaders
{
    /**
     * Origens do servidor de desenvolvimento do Vite, liberadas só no ambiente local.
     *
     * @var list<string>
     */
    private const DEV_SERVER_ORIGINS = [
        'http://localhost:5173',
        'http://[::1]:5173',
        'http://127.0.0.1:5173',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $dev = app()->isLocal() ? self::DEV_SERVER_ORIGINS : [];
        $devSockets = array_map(fn (string $origin) => str_replace('http://', 'ws://', $origin), $dev);

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$dev],
            // Em desenvolvimento o Vite injeta <style> sem nonce, e um nonce na diretiva anularia o 'unsafe-inline'.
            'style-src-elem' => $dev ? ["'self'", "'unsafe-inline'", ...$dev] : ["'self'", "'nonce-{$nonce}'"],
            // Componentes do reka-ui posicionam elementos com o atributo style.
            'style-src-attr' => ["'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'", 'data:', ...$dev],
            'connect-src' => ["'self'", ...$dev, ...$devSockets],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', $sources))
            ->values();

        if (app()->isProduction()) {
            $policy->push('upgrade-insecure-requests');
        }

        return $policy->implode('; ');
    }
}
