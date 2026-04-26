<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGeoBaseWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-GeoBase-Signature');
        $secret = config('services.geobase.webhook_secret');

        if (! $signature || ! $secret) {
            return response()->json(['error' => 'Missing signature'], 403);
        }

        // GeoBase emits "sha256=<hex>" — strip the prefix when present so
        // we always compare bare hex digests with hash_equals.
        $received = str_starts_with($signature, 'sha256=')
            ? substr($signature, 7)
            : $signature;

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $received)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
