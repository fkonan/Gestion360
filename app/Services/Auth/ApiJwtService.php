<?php

namespace App\Services\Auth;

use Illuminate\Support\Str;

class ApiJwtService
{
    private const ALGORITHM = 'HS256';
    private const RESERVED_CLAIMS = [
        'iss',
        'aud',
        'sub',
        'iat',
        'nbf',
        'exp',
        'jti',
        'scope',
    ];

    public function issueToken(string $subject, array $scopes = [], array $customClaims = []): array
    {
        $secret = $this->secret();
        if ($secret === '') {
            throw new \RuntimeException('JWT secret not configured.');
        }

        $ahora = now();
        $expiraEn = $ahora->copy()->addMinutes($this->ttlMinutes());
        $scope = $this->normalizarScopes($scopes);

        $payloadBase = [
            'iss' => $this->issuer(),
            'aud' => $this->audience(),
            'sub' => trim($subject),
            'iat' => $ahora->timestamp,
            'nbf' => $ahora->timestamp,
            'exp' => $expiraEn->timestamp,
            'jti' => (string) Str::uuid(),
            'scope' => implode(' ', $scope),
        ];
        $payload = array_merge($this->filtrarClaimsPersonalizados($customClaims), $payloadBase);

        return [
            'token' => $this->encode($payload, $secret),
            'expires_at' => $expiraEn,
            'expires_in' => max(0, $expiraEn->timestamp - $ahora->timestamp),
            'payload' => $payload,
            'scope' => $scope,
        ];
    }

    public function validateToken(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'message' => 'Token requerido.'];
        }

        $secret = $this->secret();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'JWT secret no configurado.'];
        }

        $partes = explode('.', $token);
        if (count($partes) !== 3) {
            return ['ok' => false, 'message' => 'Token invalido.'];
        }

        [$headerPart, $payloadPart, $signaturePart] = $partes;
        $header = $this->decodeJsonSegment($headerPart);
        $payload = $this->decodeJsonSegment($payloadPart);

        if (! is_array($header) || ! is_array($payload)) {
            return ['ok' => false, 'message' => 'Token invalido.'];
        }

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            return ['ok' => false, 'message' => 'Algoritmo no permitido.'];
        }

        $firmaEsperada = $this->base64UrlEncode(
            hash_hmac('sha256', $headerPart.'.'.$payloadPart, $secret, true)
        );

        if (! hash_equals($firmaEsperada, $signaturePart)) {
            return ['ok' => false, 'message' => 'Firma invalida.'];
        }

        $leeway = $this->leewaySeconds();
        $ahora = now()->timestamp;

        if (! $this->validarIssuer($payload['iss'] ?? null)) {
            return ['ok' => false, 'message' => 'Issuer invalido.'];
        }

        if (! $this->validarAudience($payload['aud'] ?? null)) {
            return ['ok' => false, 'message' => 'Audience invalido.'];
        }

        $exp = $this->toInt($payload['exp'] ?? null);
        if ($exp === null || $ahora > ($exp + $leeway)) {
            return ['ok' => false, 'message' => 'Token expirado.'];
        }

        $nbf = $this->toInt($payload['nbf'] ?? null);
        if ($nbf !== null && ($ahora + $leeway) < $nbf) {
            return ['ok' => false, 'message' => 'Token aun no valido.'];
        }

        return [
            'ok' => true,
            'payload' => $payload,
            'scopes' => $this->extractScopes($payload),
        ];
    }

    public function authenticateClient(string $clientId, string $clientSecret): ?array
    {
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        foreach ($this->configuredClients() as $client) {
            $id = trim((string) ($client['id'] ?? ''));
            $secret = trim((string) ($client['secret'] ?? ''));
            if ($id === '' || $secret === '') {
                continue;
            }

            if (hash_equals($id, $clientId) && hash_equals($secret, $clientSecret)) {
                return [
                    'id' => $id,
                    'scopes' => $this->normalizarScopes($client['scopes'] ?? []),
                ];
            }
        }

        return null;
    }

    public function canIssueRequestedScopes(array $allowedScopes, array $requestedScopes): bool
    {
        $allowed = $this->normalizarScopes($allowedScopes);
        $requested = $this->normalizarScopes($requestedScopes);

        if ($requested === []) {
            return true;
        }

        if ($allowed === []) {
            return false;
        }

        foreach ($requested as $scope) {
            if (! in_array($scope, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    public function parseScopesFromText(?string $scopeText): array
    {
        $scopeText = trim((string) $scopeText);
        if ($scopeText === '') {
            return [];
        }

        $scopes = preg_split('/\s+/', $scopeText) ?: [];

        return $this->normalizarScopes($scopes);
    }

    public function extractScopes(array $claims): array
    {
        $scopeClaim = $claims['scope'] ?? null;
        if (is_array($scopeClaim)) {
            return $this->normalizarScopes($scopeClaim);
        }

        return $this->parseScopesFromText(is_scalar($scopeClaim) ? (string) $scopeClaim : null);
    }

    public function defaultScope(): string
    {
        return trim((string) config('services.api_jwt.default_scope', ''));
    }

    private function configuredClients(): array
    {
        $raw = config('services.api_jwt.clients', []);
        if (is_array($raw) && $raw !== []) {
            $normalizados = [];
            foreach ($raw as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $normalizados[] = [
                    'id' => trim((string) ($entry['id'] ?? '')),
                    'secret' => trim((string) ($entry['secret'] ?? '')),
                    'scopes' => $entry['scopes'] ?? [],
                ];
            }

            return $normalizados;
        }

        return [];
    }

    private function normalizarScopes(array $scopes): array
    {
        return collect($scopes)
            ->map(fn ($scope) => trim((string) $scope))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function filtrarClaimsPersonalizados(array $claims): array
    {
        $filtrados = [];
        foreach ($claims as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (in_array(strtolower($key), self::RESERVED_CLAIMS, true)) {
                continue;
            }

            $filtrados[$key] = $value;
        }

        return $filtrados;
    }

    private function encode(array $payload, string $secret): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $headerPart = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadPart = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signaturePart = $this->base64UrlEncode(
            hash_hmac('sha256', $headerPart.'.'.$payloadPart, $secret, true)
        );

        return $headerPart.'.'.$payloadPart.'.'.$signaturePart;
    }

    private function decodeJsonSegment(string $segment): ?array
    {
        $raw = $this->base64UrlDecode($segment);
        if ($raw === null) {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private function validarIssuer(mixed $iss): bool
    {
        return trim((string) $iss) === $this->issuer();
    }

    private function validarAudience(mixed $aud): bool
    {
        $audienceConfigurada = $this->audience();
        if (is_string($aud)) {
            return trim($aud) === $audienceConfigurada;
        }

        if (is_array($aud)) {
            foreach ($aud as $item) {
                if (trim((string) $item) === $audienceConfigurada) {
                    return true;
                }
            }
        }

        return false;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function secret(): string
    {
        return trim((string) config('services.api_jwt.secret', ''));
    }

    private function issuer(): string
    {
        return trim((string) config('services.api_jwt.issuer', 'autogestion'));
    }

    private function audience(): string
    {
        return trim((string) config('services.api_jwt.audience', 'autogestion-api'));
    }

    private function ttlMinutes(): int
    {
        $ttl = (int) config('services.api_jwt.ttl_minutes', 60);

        if ($ttl < 5) {
            return 5;
        }

        if ($ttl > 1440) {
            return 1440;
        }

        return $ttl;
    }

    private function leewaySeconds(): int
    {
        $leeway = (int) config('services.api_jwt.leeway_seconds', 30);

        if ($leeway < 0) {
            return 0;
        }

        if ($leeway > 300) {
            return 300;
        }

        return $leeway;
    }
}
