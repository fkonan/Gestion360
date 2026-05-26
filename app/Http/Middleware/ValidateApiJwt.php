<?php

namespace App\Http\Middleware;

use App\Services\Auth\ApiJwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiJwt
{
    public function __construct(
        private readonly ApiJwtService $jwtService
    ) {}

    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $token = trim((string) $request->bearerToken());
        if ($token === '') {
            return $this->unauthorized();
        }

        $validacion = $this->jwtService->validateToken($token);
        if (! ($validacion['ok'] ?? false)) {
            return $this->unauthorized();
        }

        $claims = is_array($validacion['payload'] ?? null) ? $validacion['payload'] : [];
        $tokenScopes = is_array($validacion['scopes'] ?? null) ? $validacion['scopes'] : [];
        if (! $this->tieneScopesRequeridos($tokenScopes, $requiredScopes)) {
            return $this->forbidden();
        }

        $request->attributes->set('api_jwt_claims', $claims);
        $request->attributes->set('api_jwt_scopes', $tokenScopes);

        return $next($request);
    }

    private function tieneScopesRequeridos(array $tokenScopes, array $requiredScopes): bool
    {
        if ($this->tokenTieneScopeAdministrador($tokenScopes)) {
            return true;
        }

        $required = collect($requiredScopes)
            ->flatMap(fn ($value) => preg_split('/[,\s]+/', trim((string) $value)) ?: [])
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($required === []) {
            $defaultScope = trim((string) $this->jwtService->defaultScope());
            if ($defaultScope === '') {
                return true;
            }

            $required = [$defaultScope];
        }

        foreach ($required as $scope) {
            if (! in_array($scope, $tokenScopes, true)) {
                return false;
            }
        }

        return true;
    }

    private function tokenTieneScopeAdministrador(array $tokenScopes): bool
    {
        $tokenScopesNormalizados = collect($tokenScopes)
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokenScopesNormalizados === []) {
            return false;
        }

        $adminScopes = config('services.api_jwt.admin_scopes', []);
        if (! is_array($adminScopes)) {
            return false;
        }

        $adminScopesNormalizados = collect($adminScopes)
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($adminScopesNormalizados === []) {
            return false;
        }

        foreach ($adminScopesNormalizados as $scopeAdmin) {
            if (in_array($scopeAdmin, $tokenScopesNormalizados, true)) {
                return true;
            }
        }

        return false;
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'ok' => false,
            'message' => 'Unauthorized.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbidden(): Response
    {
        return response()->json([
            'ok' => false,
            'message' => 'Forbidden.',
        ], Response::HTTP_FORBIDDEN);
    }
}
