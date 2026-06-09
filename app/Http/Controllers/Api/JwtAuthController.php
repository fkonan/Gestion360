<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\ApiJwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JwtAuthController extends Controller
{
    public function __construct(
        private readonly ApiJwtService $jwtService
    ) {}

    public function token(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => 'nullable|string|in:client_credentials',
            'client_id' => 'required|string|max:120',
            'client_secret' => 'required|string|max:255',
            'scope' => 'nullable|string|max:1000',
            'documento_usuario' => 'nullable|string|max:50',
            'actor_documento' => 'nullable|string|max:50',
        ], [
            'client_id.required' => 'El campo client_id es obligatorio.',
            'client_secret.required' => 'El campo client_secret es obligatorio.',
            'grant_type.in' => 'El grant_type soportado es client_credentials.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $client = $this->jwtService->authenticateClient(
            (string) $request->input('client_id'),
            (string) $request->input('client_secret')
        );

        if (! is_array($client)) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $allowedScopes = is_array($client['scopes'] ?? null) ? $client['scopes'] : [];
        $requestedScopes = $this->jwtService->parseScopesFromText($request->input('scope'));
        if ($requestedScopes !== [] && ! $this->jwtService->canIssueRequestedScopes($allowedScopes, $requestedScopes)) {
            return response()->json([
                'ok' => false,
                'message' => 'Scope no permitido para el cliente.',
            ], 403);
        }

        $finalScopes = $requestedScopes;
        if ($finalScopes === []) {
            $defaultScopes = $this->jwtService->parseScopesFromText($this->jwtService->defaultScope());
            if ($defaultScopes !== []) {
                if (! $this->jwtService->canIssueRequestedScopes($allowedScopes, $defaultScopes)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Scope por defecto no permitido para el cliente.',
                    ], 403);
                }

                $finalScopes = $defaultScopes;
            } else {
                $finalScopes = $allowedScopes;
            }
        }

        if ($finalScopes === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Cliente sin scopes configurados.',
            ], 403);
        }

        try {
            $actorDocumento = trim((string) $request->input('documento_usuario', ''));
            if ($actorDocumento === '') {
                $actorDocumento = trim((string) $request->input('actor_documento', ''));
            }
            $token = $this->jwtService->issueToken(
                subject: (string) ($client['id'] ?? ''),
                scopes: $finalScopes,
                customClaims: [
                    'client_id' => (string) ($client['id'] ?? ''),
                    'actor_documento' => $actorDocumento !== '' ? $actorDocumento : null,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Error emitiendo token JWT', [
                'client_id' => (string) ($client['id'] ?? ''),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No fue posible emitir el token.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => (string) ($token['token'] ?? ''),
                'expires_in' => (int) ($token['expires_in'] ?? 0),
                'expires_at' => isset($token['expires_at']) ? $token['expires_at']->format('Y-m-d H:i:s') : null,
                'scope' => implode(' ', is_array($token['scope'] ?? null) ? $token['scope'] : []),
            ],
        ]);
    }
}
