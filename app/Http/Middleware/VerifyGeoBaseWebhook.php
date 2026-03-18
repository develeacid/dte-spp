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

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
