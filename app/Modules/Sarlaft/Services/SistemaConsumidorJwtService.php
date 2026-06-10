<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\SistemaConsumidor;
use App\Services\Auth\ApiJwtService;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticacion JWT (client_credentials) para sistemas consumidores SARLAFT.
 *
 * Valida client_id (columna `codigo`) + client_secret (hasheado en BD) contra la
 * tabla sarlaft_sistemas_consumidores y reusa el motor JWT transversal
 * (ApiJwtService) para emitir/validar el token. NO toca la config de clientes de
 * RRHH ni el ApiJwtService.
 */
class SistemaConsumidorJwtService
{
    public function __construct(
        private readonly ApiJwtService $jwtService,
    ) {}

    /**
     * Verifica las credenciales contra la tabla. Devuelve el sistema si son
     * validas (activo + secret correcto), o null.
     */
    public function autenticarCliente(string $clientId, string $clientSecret): ?SistemaConsumidor
    {
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $sistema = SistemaConsumidor::query()
            ->where('codigo', $clientId)
            ->where('estado', 'activo')
            ->whereNotNull('client_secret')
            ->first();

        if ($sistema === null) {
            return null;
        }

        if (! Hash::check($clientSecret, (string) $sistema->client_secret)) {
            return null;
        }

        return $sistema;
    }

    /**
     * Emite un JWT para el sistema con sus scopes configurados, filtrando por los
     * scopes solicitados (si se piden) siempre que esten permitidos.
     *
     * @param  array<int, string>  $scopesSolicitados
     * @return array{token: string, expires_in: int, expires_at: \Illuminate\Support\Carbon, scope: array<int, string>}|null
     */
    public function emitirToken(SistemaConsumidor $sistema, array $scopesSolicitados = []): ?array
    {
        $scopesPermitidos = $sistema->scopesArray();

        if ($scopesPermitidos === []) {
            return null;
        }

        $scopesFinales = $scopesSolicitados !== []
            ? array_values(array_intersect($scopesSolicitados, $scopesPermitidos))
            : $scopesPermitidos;

        // Si pidio scopes y ninguno esta permitido, no se emite.
        if ($scopesSolicitados !== [] && $scopesFinales === []) {
            return null;
        }

        $resultado = $this->jwtService->issueToken(
            subject: (string) $sistema->codigo,
            scopes: $scopesFinales,
            customClaims: [
                'client_id' => (string) $sistema->codigo,
                'sistema_id' => $sistema->id,
            ],
        );

        return [
            'token' => (string) ($resultado['token'] ?? ''),
            'expires_in' => (int) ($resultado['expires_in'] ?? 0),
            'expires_at' => $resultado['expires_at'],
            'scope' => is_array($resultado['scope'] ?? null) ? $resultado['scope'] : $scopesFinales,
        ];
    }
}
