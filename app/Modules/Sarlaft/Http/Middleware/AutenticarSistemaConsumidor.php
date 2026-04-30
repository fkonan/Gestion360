<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Middleware;

use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutenticarSistemaConsumidor
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => 'Token de autenticación requerido.',
            ], 401);
        }

        $sistema = SistemaConsumidor::where('api_token', $token)
            ->where('estado', 'activo')
            ->first();

        if (! $sistema) {
            return response()->json([
                'error' => 'Token inválido o sistema inactivo.',
            ], 401);
        }

        $request->merge(['sistema_consumidor' => $sistema]);

        return $next($request);
    }
}
