<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitSistema
{
    public function handle(Request $request, Closure $next): Response
    {
        $sistema = $request->get('sistema_consumidor');

        if (! $sistema) {
            return $next($request);
        }

        $key = 'api:'.$sistema->codigo;
        $maxAttempts = $sistema->limite_requests_minuto;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Límite de requests excedido.',
                'retry_after_seconds' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($key, $maxAttempts));

        return $response;
    }
}
