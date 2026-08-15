<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec §10: CSP with nonces (no unsafe-inline), HSTS, frame denial, nosniff,
 * strict referrer, and a deny-by-default Permissions-Policy.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // One nonce per request; @vite picks it up automatically and the
        // layout passes it to @livewireStyles/@livewireScripts.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    protected function contentSecurityPolicy(string $nonce): string
    {
        // The Vite dev server serves assets from its own origin locally
        $vitePort = config('app.vite_dev_port');
        $dev = app()->environment('local')
            ? " http://localhost:{$vitePort} ws://localhost:{$vitePort}"
            : '';

        // 'unsafe-eval' is required by Alpine's expression evaluator; inline
        // scripts and styles are nonce-only (spec §10 bans unsafe-inline).
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$dev}",
            "style-src 'self' 'nonce-{$nonce}'{$dev}",
            "img-src 'self' data:{$dev}",
            "font-src 'self'{$dev}",
            "connect-src 'self'{$dev}",
            "frame-ancestors 'none'",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
