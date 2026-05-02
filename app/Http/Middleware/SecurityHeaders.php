<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $viteOrigin = app()->environment('local') ? ' http://localhost:5173 http://127.0.0.1:5173' : '';
        $wsOrigin = app()->environment('local') ? ' ws://localhost:5173 ws://127.0.0.1:5173' : '';

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com{$viteOrigin}; "
            ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net{$viteOrigin}; "
            ."font-src 'self' https://fonts.gstatic.com; "
            ."img-src 'self' data: https://ui-avatars.com; "
            ."connect-src 'self'{$viteOrigin}{$wsOrigin}; "
            ."frame-ancestors 'none'"
        );

        return $response;
    }
}
