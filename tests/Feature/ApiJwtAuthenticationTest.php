<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\JwtAuthController;
use App\Http\Middleware\ValidateApiJwt;
use App\Services\Auth\ApiJwtService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiJwtAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.api_jwt.secret' => 'test-secret-for-api-jwt',
            'services.api_jwt.issuer' => 'test-issuer',
            'services.api_jwt.audience' => 'test-audience',
            'services.api_jwt.ttl_minutes' => 60,
            'services.api_jwt.leeway_seconds' => 0,
            'services.api_jwt.default_scope' => 'empleados.permisos',
            'services.api_jwt.clients' => [
                [
                    'id' => 'mobile-backend',
                    'secret' => 'client-secret',
                    'scopes' => ['empleados.permisos', 'sarlaft.consultas'],
                ],
            ],
        ]);
    }

    public function test_reserved_claims_cannot_be_overridden_by_custom_claims(): void
    {
        $jwtService = app(ApiJwtService::class);

        $issued = $jwtService->issueToken('mobile-backend', ['empleados.permisos'], [
            'exp' => 1,
            'scope' => 'admin.full',
            'client_id' => 'mobile-backend',
        ]);

        $validated = $jwtService->validateToken($issued['token']);

        $this->assertTrue($validated['ok']);
        $this->assertNotSame(1, $validated['payload']['exp']);
        $this->assertSame(['empleados.permisos'], $validated['scopes']);
        $this->assertSame('mobile-backend', $validated['payload']['client_id']);
    }

    public function test_token_endpoint_rejects_client_without_allowed_scopes(): void
    {
        config([
            'services.api_jwt.clients' => [
                [
                    'id' => 'mobile-backend',
                    'secret' => 'client-secret',
                    'scopes' => [],
                ],
            ],
        ]);

        $response = $this->tokenControllerResponse([
            'grant_type' => 'client_credentials',
            'client_id' => 'mobile-backend',
            'client_secret' => 'client-secret',
        ]);

        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['ok']);
        $this->assertSame('Scope por defecto no permitido para el cliente.', $payload['message']);
    }

    public function test_token_endpoint_uses_default_scope_only_when_allowed(): void
    {
        $response = $this->tokenControllerResponse([
            'grant_type' => 'client_credentials',
            'client_id' => 'mobile-backend',
            'client_secret' => 'client-secret',
        ]);

        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bearer', $payload['data']['token_type']);
        $this->assertSame('empleados.permisos', $payload['data']['scope']);
    }

    public function test_protected_route_returns_forbidden_when_token_lacks_required_scope(): void
    {
        $jwtService = app(ApiJwtService::class);
        $issued = $jwtService->issueToken('mobile-backend', ['sarlaft.consultas']);

        $request = Request::create('/api/v2/empleados/permisos/radicar/schema', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$issued['token']);

        $response = app(ValidateApiJwt::class)->handle(
            $request,
            fn () => response()->json(['ok' => true]),
            'empleados.permisos'
        );

        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['ok']);
        $this->assertSame('Forbidden.', $payload['message']);
    }

    private function tokenControllerResponse(array $payload)
    {
        $request = Request::create('/api/v2/auth/token', 'POST', $payload);
        $request->headers->set('Accept', 'application/json');

        return app(JwtAuthController::class)->token($request);
    }
}
