<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LogRrhhRequestConsumption
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $inicio = microtime(true);
        $requestId = (string) Str::uuid();
        $canal = $request->is('api/*') ? 'API' : 'WEB';

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            if ($this->debeRegistrarError($canal, $request)) {
                $this->registrarConsumo(
                    request: $request,
                    response: null,
                    requestId: $requestId,
                    canal: $canal,
                    duracionMs: round((microtime(true) - $inicio) * 1000, 2),
                    evento: 'ERROR',
                    error: [
                        'tipo' => $e::class,
                        'mensaje' => Str::limit($e->getMessage(), 500),
                    ]
                );
            }

            throw $e;
        }

        if (! $this->debeRegistrar($canal, $request, $response)) {
            return $response;
        }

        $evento = $this->resolverEvento($canal, $request, $response);
        $duracionMs = round((microtime(true) - $inicio) * 1000, 2);
        $this->registrarConsumo(
            request: $request,
            response: $response,
            requestId: $requestId,
            canal: $canal,
            duracionMs: $duracionMs,
            evento: $evento
        );

        return $response;
    }

    private function registrarConsumo(
        Request $request,
        ?Response $response,
        string $requestId,
        string $canal,
        float $duracionMs,
        string $evento,
        ?array $error = null
    ): void {

        $route = $request->route();
        $claims = $request->attributes->get('api_jwt_claims', []);
        $claims = is_array($claims) ? $claims : [];

        $actorDocumentoClaim = trim((string) ($claims['actor_documento'] ?? ''));
        $actorDocumentoRequest = trim((string) ($request->input('documento_actor', $request->input('documento_usuario', ''))));

        $actorDocumento = $actorDocumentoRequest !== '' ? $actorDocumentoRequest : $actorDocumentoClaim;

        $payload = [
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String(),
            'canal' => $canal,
            'evento' => $evento,
            'request' => [
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route_name' => $route?->getName(),
                'ip' => $request->ip(),
                'user_agent' => trim((string) $request->userAgent()),
                'query' => $this->sanitizar($request->query()),
                'body' => $this->sanitizar($request->except(['password', 'client_secret', 'secret'])),
                'files' => $this->extraerMetadatosArchivos($request),
            ],
            'actor' => [
                'documento' => $actorDocumento !== '' ? $actorDocumento : null,
                'id_usuario_web' => $request->user()?->IdUsuario ?? null,
                'usuario_web' => $request->user()?->Usuario ?? null,
            ],
            'response' => [
                'status' => $response?->getStatusCode() ?? 500,
                'ok' => $response?->isSuccessful() ?? false,
                'message' => ($canal === 'API' && $response) ? $this->extraerMensajeRespuesta($response) : null,
            ],
            'duration_ms' => $duracionMs,
        ];

        if ($error !== null) {
            $payload['error'] = $error;
        }

        try {
            $tag = sprintf('RRHH_NOVEDADES_%s_%s', strtoupper($canal), strtoupper($evento));
            Log::channel('rrhh_novedades')->info($tag, $payload);
        } catch (\Throwable) {
            // No interrumpir la operacion principal por una falla de escritura de logs.
        }
    }

    private function debeRegistrar(string $canal, Request $request, Response $response): bool
    {
        if ($canal === 'API') {
            return true;
        }

        if ($response->getStatusCode() >= 400) {
            return true;
        }

        return $this->esRadicacionWeb($request);
    }

    private function debeRegistrarError(string $canal, Request $request): bool
    {
        if ($canal === 'API') {
            return true;
        }

        return $this->esRadicacionWeb($request) || str_starts_with('/'.ltrim($request->path(), '/'), '/gestionRRHH/');
    }

    private function resolverEvento(string $canal, Request $request, Response $response): string
    {
        if ($response->getStatusCode() >= 400) {
            return 'ERROR';
        }

        if ($canal === 'WEB' && $this->esRadicacionWeb($request)) {
            return 'RADICACION';
        }

        if ($canal === 'API' && $this->esRadicacionApi($request)) {
            return 'RADICACION';
        }

        return 'CONSULTA';
    }

    private function esRadicacionWeb(Request $request): bool
    {
        if (strtoupper($request->method()) !== 'POST') {
            return false;
        }

        $routeName = trim((string) ($request->route()?->getName() ?? ''));
        if ($routeName === '') {
            return false;
        }

        $radicaciones = [
            'gestionRRHH.permisos.store',
            'gestionRRHH.permisos.incapacidades.store',
            'gestionRRHH.permisos.vacaciones.store',
            'gestionRRHH.permisos.permisos-permanentes.store',
            'gestionRRHH.descargos.citaciones.store',
        ];

        return in_array($routeName, $radicaciones, true);
    }

    private function esRadicacionApi(Request $request): bool
    {
        if (strtoupper($request->method()) !== 'POST') {
            return false;
        }

        $path = '/'.ltrim($request->path(), '/');

        $radicaciones = [
            '/api/v2/empleados/permisos/radicar',
            '/api/v2/empleados/incapacidades/radicar',
            '/api/v2/empleados/vacaciones/radicar',
            '/api/v2/empleados/permisos-permanentes/radicar',
        ];

        return in_array($path, $radicaciones, true);
    }

    private function extraerMensajeRespuesta(Response $response): ?string
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return null;
        }

        $content = trim((string) $response->getContent());
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $msg = trim((string) ($decoded['message'] ?? ''));

            return $msg !== '' ? Str::limit($msg, 500) : null;
        }

        return Str::limit($content, 500);
    }

    private function extraerMetadatosArchivos(Request $request): array
    {
        $archivos = [];

        foreach ($request->allFiles() as $clave => $file) {
            if (is_array($file)) {
                foreach ($file as $idx => $subfile) {
                    if (is_array($subfile)) {
                        foreach ($subfile as $subidx => $deepFile) {
                            if ($deepFile) {
                                $archivos[] = $this->mapArchivo("{$clave}.{$idx}.{$subidx}", $deepFile);
                            }
                        }
                    } elseif ($subfile) {
                        $archivos[] = $this->mapArchivo("{$clave}.{$idx}", $subfile);
                    }
                }
            } elseif ($file) {
                $archivos[] = $this->mapArchivo($clave, $file);
            }
        }

        return $archivos;
    }

    private function mapArchivo(string $campo, mixed $file): array
    {
        return [
            'campo' => $campo,
            'nombre' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
            'extension' => method_exists($file, 'getClientOriginalExtension') ? $file->getClientOriginalExtension() : null,
            'size_bytes' => method_exists($file, 'getSize') ? $file->getSize() : null,
            'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : null,
        ];
    }

    private function sanitizar(mixed $valor, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return '[MAX_DEPTH]';
        }

        if (is_array($valor)) {
            $out = [];
            foreach ($valor as $k => $v) {
                $key = is_string($k) ? strtolower($k) : (string) $k;
                if ($this->esCampoSensible($key)) {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = $this->sanitizar($v, $depth + 1);
            }

            return $out;
        }

        if (is_object($valor)) {
            return '[OBJECT]';
        }

        if (is_string($valor)) {
            return Str::limit($valor, 500);
        }

        return $valor;
    }

    private function esCampoSensible(string $key): bool
    {
        $sensibles = [
            'password',
            'secret',
            'token',
            'authorization',
            'api_key',
            'archivo',
            'file',
            'carta',
            'documento_soporte',
            'pdf',
            'data_base64',
        ];

        foreach ($sensibles as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
