<?php

namespace App\Modules\GestionRRHH\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DesbloqueoConductoresApiService
{
    public function desbloquearIdentificacion(string $identificacion): array
    {
        $documento = $this->normalizarDocumento($identificacion);
        $resultadoLote = $this->desbloquearIdentificaciones([$documento]);

        if (! $resultadoLote['success']) {
            return [
                'documento' => $documento,
                'success' => false,
                'status' => 'error',
                'message' => $resultadoLote['message'],
                'http_status' => $resultadoLote['http_status'] ?? null,
            ];
        }

        $resultadoDocumento = $resultadoLote['resultados'][$documento] ?? null;

        if (! $resultadoDocumento) {
            return [
                'documento' => $documento,
                'success' => false,
                'status' => 'error',
                'message' => 'La API no retorno resultado para el conductor solicitado.',
                'http_status' => $resultadoLote['http_status'] ?? null,
            ];
        }

        return array_merge($resultadoDocumento, [
            'http_status' => $resultadoLote['http_status'] ?? null,
        ]);
    }

    public function desbloquearIdentificaciones(array $identificaciones): array
    {
        $documentos = collect($identificaciones)
            ->map(fn ($id) => $this->normalizarDocumento($id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($documentos)) {
            return $this->errorResultado('No se recibieron identificaciones validas para desbloqueo.');
        }

        if (! $this->configuracionCompleta()) {
            $this->logger()->error('Brontobyte desbloqueo: configuracion incompleta', [
                'base_url_configured' => ! empty(config('apiBrontobyte.base_url')),
                'username_configured' => ! empty(config('apiBrontobyte.username')),
                'password_configured' => ! empty(config('apiBrontobyte.password')),
            ]);

            return $this->errorResultado('No fue posible configurar la conexion de desbloqueo.');
        }

        try {
            $token = $this->obtenerToken();

            if (! $token) {
                return $this->errorResultado('Error autenticando con el servicio de desbloqueo.');
            }

            $response = $this->requestDesbloqueo($token, $documentos);

            if ($response->status() === 401) {
                Cache::forget(config('apiBrontobyte.token_cache_key'));

                $this->logger()->warning('Brontobyte desbloqueo: token invalido o expirado, se reintenta autenticacion');

                $token = $this->obtenerToken(true);

                if (! $token) {
                    return $this->errorResultado('No se pudo renovar la autenticacion para desbloqueo.', 401);
                }

                $response = $this->requestDesbloqueo($token, $documentos);
            }

            return $this->normalizarRespuestaDesbloqueo($response, $documentos);
        } catch (ConnectionException $e) {
            $this->logger()->error('Brontobyte desbloqueo: timeout o error de conexion', [
                'documents' => $documentos,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResultado('No fue posible conectar con el servicio de desbloqueo (timeout/conexion).');
        } catch (\Throwable $e) {
            $this->logger()->error('Brontobyte desbloqueo: excepcion no controlada', [
                'documents' => $documentos,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResultado('Ocurrio un error procesando el desbloqueo de conductor.');
        }
    }

    private function obtenerToken(bool $forceRefresh = false): ?string
    {
        $tokenCacheKey = config('apiBrontobyte.token_cache_key');

        if (! $forceRefresh) {
            $tokenCacheado = Cache::get($tokenCacheKey);

            if (is_string($tokenCacheado) && $tokenCacheado !== '') {
                return $tokenCacheado;
            }
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((float) config('apiBrontobyte.connect_timeout'))
                ->timeout((float) config('apiBrontobyte.timeout'))
                ->post($this->buildUrl('/auth'), [
                    'username' => config('apiBrontobyte.username'),
                    'password' => config('apiBrontobyte.password'),
                ]);
        } catch (ConnectionException $e) {
            $this->logger()->error('Brontobyte auth: timeout o error de conexion', [
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            $this->logger()->error('Brontobyte auth: excepcion no controlada', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            $this->logger()->warning('Brontobyte auth: respuesta no exitosa', [
                'status' => $response->status(),
                'message' => $this->extraerMensajeRespuesta($response->json(), $response->body(), 'Error autenticando con Brontobyte'),
            ]);

            return null;
        }

        $payload = $response->json();
        $token = is_array($payload) ? ($payload['token'] ?? null) : null;

        if (! is_string($token) || $token === '') {
            $this->logger()->error('Brontobyte auth: respuesta invalida sin token');

            return null;
        }

        Cache::put($tokenCacheKey, $token, $this->resolverExpiracionToken($payload['expiresAt'] ?? null));

        return $token;
    }

    private function requestDesbloqueo(string $token, array $documentos): Response
    {
        return Http::acceptJson()
            ->withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->connectTimeout((float) config('apiBrontobyte.connect_timeout'))
            ->timeout((float) config('apiBrontobyte.timeout'))
            ->post($this->buildUrl('/pasajes/desbloqueoConductor'), [
                'identificaciones' => $documentos,
            ]);
    }

    private function normalizarRespuestaDesbloqueo(Response $response, array $documentos): array
    {
        $payload = $response->json();
        $httpStatus = $response->status();

        if (! $response->successful()) {
            $mensaje = $this->extraerMensajeRespuesta(
                is_array($payload) ? $payload : null,
                $response->body(),
                'Error consumiendo API de desbloqueo.'
            );

            $this->logger()->warning('Brontobyte desbloqueo: respuesta no exitosa', [
                'documents' => $documentos,
                'status' => $httpStatus,
                'message' => $mensaje,
            ]);

            return $this->errorResultado($mensaje, $httpStatus);
        }

        if (! is_array($payload)) {
            $this->logger()->error('Brontobyte desbloqueo: respuesta invalida (no JSON)', [
                'documents' => $documentos,
                'status' => $httpStatus,
            ]);

            return $this->errorResultado('La API de desbloqueo retorno una respuesta invalida.', $httpStatus);
        }

        $resultadosApi = $payload['resultados'] ?? null;

        if (! is_array($resultadosApi) || count($resultadosApi) === 0) {
            $this->logger()->warning('Brontobyte desbloqueo: lista de resultados vacia', [
                'documents' => $documentos,
                'status' => $httpStatus,
            ]);

            return $this->errorResultado('La API no retorno resultados de desbloqueo para los conductores enviados.', $httpStatus);
        }

        $resultadosNormalizados = [];

        foreach ($documentos as $documento) {
            $resultadoDocumento = $this->buscarResultadoDocumento($resultadosApi, $documento);

            if (! $resultadoDocumento) {
                $resultadosNormalizados[$documento] = [
                    'documento' => $documento,
                    'success' => false,
                    'status' => 'error',
                    'message' => 'La API no retorno el resultado del conductor enviado.',
                ];
                continue;
            }

            $mensaje = trim((string) ($resultadoDocumento['message'] ?? ''));
            $success = $this->toBoolean($resultadoDocumento['success'] ?? false);
            $esSinBloqueos = Str::contains(Str::lower($mensaje), 'no tiene bloqueos');
            $estado = $success ? 'unlocked' : ($esSinBloqueos ? 'no_blocks' : 'error');

            $resultadosNormalizados[$documento] = [
                'documento' => (string) ($resultadoDocumento['documento'] ?? $documento),
                'success' => $estado === 'unlocked',
                'status' => $estado,
                'message' => $mensaje !== '' ? $mensaje : $this->mensajePorDefectoEstado($estado),
            ];
        }

        return [
            'success' => true,
            'status' => 'processed',
            'http_status' => $httpStatus,
            'message' => (string) ($payload['message'] ?? ''),
            'api_success' => $this->toBoolean($payload['success'] ?? true),
            'resultados' => $resultadosNormalizados,
        ];
    }

    private function buscarResultadoDocumento(array $resultadosApi, string $documento): ?array
    {
        foreach ($resultadosApi as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->normalizarDocumento($item['documento'] ?? '') === $documento) {
                return $item;
            }
        }

        return null;
    }

    private function resolverExpiracionToken($expiresAt): \DateTimeInterface
    {
        $fallback = now()->addMinutes((int) config('apiBrontobyte.token_cache_minutes', 55));

        if (empty($expiresAt) || ! is_string($expiresAt)) {
            return $fallback;
        }

        try {
            $fechaExpiracion = Carbon::parse($expiresAt)->subSeconds(30);

            return $fechaExpiracion->greaterThan(now()) ? $fechaExpiracion : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function extraerMensajeRespuesta(?array $payload, string $body, string $fallback): string
    {
        if (is_array($payload)) {
            foreach (['message', 'error', 'detalle', 'detail'] as $key) {
                if (! empty($payload[$key]) && is_string($payload[$key])) {
                    return $payload[$key];
                }
            }
        }

        $bodyLimpio = trim($body);

        if ($bodyLimpio !== '') {
            return Str::limit($bodyLimpio, 220);
        }

        return $fallback;
    }

    private function normalizarDocumento($documento): string
    {
        return preg_replace('/\D+/', '', (string) $documento) ?? '';
    }

    private function mensajePorDefectoEstado(string $estado): string
    {
        return match ($estado) {
            'unlocked' => 'Conductor desbloqueado.',
            'no_blocks' => 'El conductor no tiene bloqueos.',
            default => 'No fue posible desbloquear el conductor.',
        };
    }

    private function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    private function buildUrl(string $path): string
    {
        return rtrim((string) config('apiBrontobyte.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function configuracionCompleta(): bool
    {
        return ! empty(config('apiBrontobyte.base_url'))
            && ! empty(config('apiBrontobyte.username'))
            && ! empty(config('apiBrontobyte.password'));
    }

    private function errorResultado(string $message, ?int $httpStatus = null): array
    {
        return [
            'success' => false,
            'status' => 'error',
            'message' => $message,
            'http_status' => $httpStatus,
            'resultados' => [],
        ];
    }

    private function logger()
    {
        return Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/gestionrrhh_desbloqueo_preoperacional.log'),
            'days' => (int) config('apiBrontobyte.log_days', 7),
        ]);
    }
}
