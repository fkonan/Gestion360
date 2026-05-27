<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmployeePermitsApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = trim((string) config('services.employee_permits.key'));
        if ($configuredKey === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Employee permits API key not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $providedKey = trim((string) ($request->header('X-API-KEY') ?: $request->bearerToken()));
        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}

