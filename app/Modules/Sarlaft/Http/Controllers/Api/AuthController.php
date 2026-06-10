<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Api\TokenRequest;
use App\Modules\Sarlaft\Services\SistemaConsumidorJwtService;
use App\Services\Auth\ApiJwtService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly SistemaConsumidorJwtService $jwtService,
        private readonly ApiJwtService $apiJwtService,
    ) {}

    /**
     * Emite un JWT para un sistema consumidor (grant client_credentials).
     * client_id = codigo del sistema, client_secret validado contra la tabla.
     */
    public function token(TokenRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $sistema = $this->jwtService->autenticarCliente(
            (string) $datos['client_id'],
            (string) $datos['client_secret'],
        );

        if ($sistema === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciales invalidas o sistema inactivo.',
            ], 401);
        }

        $scopesSolicitados = $this->apiJwtService->parseScopesFromText($datos['scope'] ?? null);
        $resultado = $this->jwtService->emitirToken($sistema, $scopesSolicitados);

        if ($resultado === null) {
            return response()->json([
                'ok' => false,
                'message' => 'El sistema no tiene scopes permitidos para los solicitados.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $resultado['token'],
                'expires_in' => $resultado['expires_in'],
                'expires_at' => $resultado['expires_at']?->format('Y-m-d H:i:s'),
                'scope' => implode(' ', $resultado['scope']),
            ],
        ]);
    }
}
