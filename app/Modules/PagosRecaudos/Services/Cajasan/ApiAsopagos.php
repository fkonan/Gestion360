<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiAsopagos
{
  private const API_LOG_CHANNEL = 'pagos_recaudos_api';

  private const API_RESPONSE_ARCHIVE_CHANNEL = 'pagos_recaudos_api_respuestas';

  private const API_SENSITIVE_KEYS = [
    'password',
    'auth_password',
    'client_secret',
    'token',
    'access_token',
    'refresh_token',
    'authorization',
    'secret',
  ];

  private const CITY_CODE_OVERRIDES = [
    '68233' => '68081',  //Dagota no existe en DIVIPOLA, se usa municipio vecino (Barrancabermeja)
    '20430' => '20250',   //Municipio vecino
    '76892' => '76001'  //Municipio vecino
  ];

  private const TRANSACTION_TYPE_CONSULTA = '10';

  private const TRANSACTION_TYPE_RETIRO = '01';

  private const TRANSACTION_TYPE_REVERSO = '03';

  public function obtenerToken(): array
  {
    $tokenCacheKey = config('apiAsopagos.token_cache_key');
    $token = Cache::get($tokenCacheKey);

    if ($token) {
      return ['token' => $token];
    }

    try {
      $request = Http::asForm()->timeout($this->tokenTimeoutSeconds());
      $retryAttempts = $this->tokenRetryAttempts();

      if ($retryAttempts > 1) {
        $request = $request->retry($retryAttempts, $this->tokenRetrySleepMs(), function ($exception) {
          if (! $exception instanceof ConnectionException || $this->esTimeoutThrowable($exception)) {
            return false;
          }

          PagosRecaudosLogger::warning('Reintento al obtener token de Asopagos', [
            'operation' => 'api_asopagos',
            'stage' => 'token_retry',
            'exception_type' => get_class($exception),
            'timestamp' => now(),
          ]);

          return true;
        });
      }

      $tokenPayload = [
        'username' => config('apiAsopagos.credentials.auth_username'),
        'password' => config('apiAsopagos.credentials.auth_password'),
        'grant_type' => 'password',
        'client_id' => config('apiAsopagos.credentials.client_id'),
        'client_secret' => config('apiAsopagos.credentials.client_secret'),
        'scope' => config('apiAsopagos.credentials.scope'),
      ];
      $response = $request->post(config('apiAsopagos.token_url'), $tokenPayload);

      if ($response->successful()) {
        $data = $this->decodificarJson($response);
        $token = $data['access_token'] ?? null;

        if ($token) {
          Cache::put($tokenCacheKey, $token, now()->addMinutes(config('apiAsopagos.token_cache_minutes')));

          return ['token' => $token];
        }

        PagosRecaudosLogger::error('Respuesta de token sin access_token', [
          'operation' => 'api_asopagos',
          'stage' => 'token_response',
          'response' => $data,
        ]);

        return ['error' => 'Token no recibido'];
      }

      PagosRecaudosLogger::error('No se pudo obtener el token de Asopagos', [
        'operation' => 'api_asopagos',
        'stage' => 'token_response',
        'status' => $response->status(),
        'response' => $response->body(),
      ]);

      $data = $this->decodificarJson($response);

      return [
        'error' => $this->extraerMensajeError($data, 'No fue posible autenticarse con Asopagos.'),
        'responseCode' => false,
        'status' => $response->status(),
        'body' => $response->body(),
        'transport_error' => false,
        'is_uncertain' => false,
        'error_category' => $this->determinarCategoriaError($response->status(), $data),
      ];
    } catch (ConnectionException $e) {
      PagosRecaudosLogger::exception('Error de transporte al obtener token de Asopagos', $e, [
        'operation' => 'api_asopagos',
        'stage' => 'token_request',
      ]);

      return [
        'error' => $this->mensajeErrorTransporte($e, 'No fue posible comunicarse con el servicio de autenticacion de Asopagos.'),
        'responseCode' => false,
        'status' => null,
        'transport_error' => true,
        'is_uncertain' => false,
        'error_category' => $this->esTimeoutThrowable($e) ? 'timeout' : 'transport_error',
      ];
    } catch (Throwable $e) {
      PagosRecaudosLogger::exception('Excepcion al obtener token de Asopagos', $e, [
        'operation' => 'api_asopagos',
        'stage' => 'token_request',
      ]);

      return [
        'error' => 'Excepcion: '.$e->getMessage(),
        'responseCode' => false,
        'status' => null,
        'transport_error' => false,
        'is_uncertain' => false,
        'error_category' => 'internal_error',
      ];
    }
  }

  private function ejecutarTransaccion(array $datos, ?int $transactionId = null, ?int $sequenceId = null): array
  {
    $startedAt = microtime(true);
    $tokenResponse = $this->obtenerToken();
    if (isset($tokenResponse['error'])) {
      return [
        'error' => $tokenResponse['error'],
        'responseCode' => false,
        'transactionId' => $transactionId,
        'sequenceId' => $sequenceId,
        'status' => $tokenResponse['status'] ?? null,
        'transport_error' => $tokenResponse['transport_error'] ?? false,
        'is_uncertain' => $tokenResponse['is_uncertain'] ?? false,
        'error_category' => $tokenResponse['error_category'] ?? null,
      ];
    }

    $token = $tokenResponse['token'];
    $payload = array_merge([
      'partnerId' => config('apiAsopagos.partner_id'),
      'originId' => config('apiAsopagos.origin_id'),
      'clientId' => config('apiAsopagos.client_id_value'),
      'sequenceId' => $sequenceId,
      'transactionId' => $transactionId,
      'currencyCode' => '170',
      'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
      'businessLine' => '03',
      'user' => config('apiAsopagos.credentials.username'),
      'password' => config('apiAsopagos.credentials.password'),
    ], $datos);

    try {
      $this->logApi('transaction.request', [
        'url' => config('apiAsopagos.base_url'),
        'payload' => $payload,
      ]);
      $this->logApiResponseArchive('transaction.request', [
        'transactionType' => $payload['transactionType'] ?? null,
        'transactionId' => $payload['transactionId'] ?? null,
        'sequenceId' => $payload['sequenceId'] ?? null,
        'payload' => $payload,
      ]);

      $response = Http::withToken($token)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->timeout($this->transactionTimeoutSeconds())
        ->post(config('apiAsopagos.base_url'), $payload);

      $data = $this->decodificarJson($response);
      $this->logApi('transaction.response', [
        'url' => config('apiAsopagos.base_url'),
        'http_status' => $response->status(),
        'transactionType' => $payload['transactionType'] ?? null,
        'transactionId' => $payload['transactionId'] ?? null,
        'sequenceId' => $payload['sequenceId'] ?? null,
        'response' => $data,
      ]);
      $this->logApiResponseArchive('transaction.response', [
        'http_status' => $response->status(),
        'transactionType' => $payload['transactionType'] ?? null,
        'transactionId' => $payload['transactionId'] ?? null,
        'sequenceId' => $payload['sequenceId'] ?? null,
        'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
        'payload' => $payload,
        'response' => $data,
      ]);

      if (! $data || ! is_array($data)) {
        PagosRecaudosLogger::error('Respuesta invalida de Asopagos', [
          'operation' => 'api_asopagos',
          'stage' => 'transaction_response',
          'status' => $response->status(),
          'body' => $response->body(),
        ]);

        return [
          'error' => 'Respuesta invalida del servidor Asopagos',
          'transactionId' => $payload['transactionId'],
          'sequenceId' => $payload['sequenceId'],
          'status' => $response->status(),
          'body' => $response->body(),
          'responseCode' => false,
          'is_uncertain' => $this->esTransaccionMonetaria($payload),
          'transport_error' => false,
          'error_category' => 'invalid_response',
        ];
      }

      if ($response->successful()) {
        if (isset($data['responseCode']) && $data['responseCode'] == true) {
          return array_merge([
            'sequenceId' => $payload['sequenceId'],
            'transport_error' => false,
            'is_uncertain' => false,
            'error_category' => null,
          ], $data);
        }

        PagosRecaudosLogger::error('Error al ejecutar transaccion con Asopagos', [
          'operation' => 'api_asopagos',
          'stage' => 'transaction_response',
          'status' => $response->status(),
          'payload' => collect($payload)->except(['user', 'password'])->all(),
          'response' => $data,
        ]);

        return $this->normalizarRespuestaError(
          $data,
          $payload,
          $response->status(),
          'La transaccion fue rechazada por Asopagos.'
        );
      }

      return $this->normalizarRespuestaError(
        $data,
        $payload,
        $response->status(),
        'Respuesta HTTP no exitosa de Asopagos.',
        $response->body()
      );
    } catch (ConnectionException $e) {
      $this->logApi('transaction.exception', [
        'url' => config('apiAsopagos.base_url'),
        'transactionType' => $payload['transactionType'] ?? null,
        'transactionId' => $payload['transactionId'] ?? null,
        'sequenceId' => $payload['sequenceId'] ?? null,
        'exception_class' => $e::class,
        'exception_message' => $e->getMessage(),
      ], 'error');

      PagosRecaudosLogger::exception('Excepcion de transporte al ejecutar transaccion con Asopagos', $e, [
        'operation' => 'api_asopagos',
        'stage' => 'transaction_request',
        'payload' => collect($payload)->except(['user', 'password'])->all(),
      ]);

      return $this->normalizarErrorTransporte(
        $e,
        $payload,
        $this->esTransaccionMonetaria($payload),
        'No fue posible confirmar el estado final de la transaccion con Asopagos.'
      );
    } catch (Throwable $e) {
      $this->logApi('transaction.exception', [
        'url' => config('apiAsopagos.base_url'),
        'transactionType' => $payload['transactionType'] ?? null,
        'transactionId' => $payload['transactionId'] ?? null,
        'sequenceId' => $payload['sequenceId'] ?? null,
        'exception_class' => $e::class,
        'exception_message' => $e->getMessage(),
      ], 'error');

      PagosRecaudosLogger::exception('Excepcion al ejecutar transaccion con Asopagos', $e, [
        'operation' => 'api_asopagos',
        'stage' => 'transaction_request',
        'payload' => collect($payload)->except(['user', 'password'])->all(),
      ]);

      return $this->normalizarErrorTransporte(
        $e,
        $payload,
        $this->esTransaccionMonetaria($payload),
        'No fue posible confirmar el estado final de la transaccion con Asopagos.'
      );
    }
  }

  public function consultarSaldo(string $tipoDoc, string $documento, int|string $departamento, int|string $ciudad): array
  {
    return $this->ejecutarTransaccion([
      'transactionType' => self::TRANSACTION_TYPE_CONSULTA,
      'currencyCode' => null,
      'state' => $departamento,
      'city' => $this->normalizarCiudadParaApi($ciudad),
      'identificationType' => $tipoDoc,
      'identification' => $documento,
    ]);
  }

  public function retirar(string $tipoDoc, string $documento, string $monto, int|string $departamento, int|string $ciudad, int $transactionId, int $sequenceId): array
  {
    $resultado = $this->ejecutarTransaccion([
      'transactionType' => self::TRANSACTION_TYPE_RETIRO,
      'amountTran' => $monto,
      'state' => $departamento,
      'city' => $this->normalizarCiudadParaApi($ciudad),
      'identificationType' => $tipoDoc,
      'identification' => $documento,
    ], $transactionId, $sequenceId);

    if ($this->debeIntentarReverso($resultado)) {
      $esTimeout = ($resultado['error_category'] ?? null) === 'timeout';
      $statusConReverso = $esTimeout ? 'fallo_timeout_con_reverso' : 'fallo_incierto_con_reverso';
      $statusSinReverso = $esTimeout ? 'fallo_timeout_sin_reverso' : 'fallo_incierto_sin_reverso';

      PagosRecaudosLogger::warning('Fallo incierto en retiro. Iniciando reverso automatico', [
        'operation' => 'api_asopagos',
        'stage' => 'retiro_reverso_automatico',
        'transactionId' => $resultado['transactionId'] ?? null,
        'sequenceId' => $resultado['sequenceId'] ?? null,
        'error_category' => $resultado['error_category'] ?? null,
        'error' => $resultado['error'] ?? null,
      ]);

      $reverso = $this->reversoRetiro(
        $tipoDoc,
        $documento,
        $monto,
        $departamento,
        $ciudad,
        $resultado['transactionId'],
        $resultado['sequenceId']
      );

      if (
        empty($reverso) ||
        (isset($reverso['responseCode']) && $reverso['responseCode'] == false) ||
        isset($reverso['error'])
      ) {
        PagosRecaudosLogger::critical('Fallo reverso tras retiro incierto. Accion manual requerida.', [
          'operation' => 'api_asopagos',
          'stage' => 'retiro_reverso_automatico',
          'retiro_error' => $resultado['error'] ?? ($resultado['errorID'] ?? 'Error desconocido'),
          'reverso_error' => $reverso['error'] ?? ($reverso['errorID'] ?? null),
          'transactionId' => $resultado['transactionId'],
          'sequenceId' => $resultado['sequenceId'],
          'monto' => $monto,
          'tipoDoc' => $tipoDoc,
          'documento' => $documento,
          'departamento' => $departamento,
          'ciudad' => $ciudad,
        ]);

        return [
          'status' => $statusSinReverso,
          'error' => $resultado['error'] ?? 'Error desconocido',
          'reverso' => $reverso,
          'responseCode' => false,
          'is_uncertain' => true,
          'error_category' => $resultado['error_category'] ?? null,
          'transactionId' => $resultado['transactionId'] ?? null,
          'sequenceId' => $resultado['sequenceId'] ?? null,
        ];
      }

      return [
        'status' => $statusConReverso,
        'error' => $resultado['error'] ?? 'Error desconocido',
        'reverso' => $reverso,
        'responseCode' => false,
        'is_uncertain' => true,
        'error_category' => $resultado['error_category'] ?? null,
        'transactionId' => $resultado['transactionId'] ?? null,
        'sequenceId' => $resultado['sequenceId'] ?? null,
      ];
    }

    return $resultado;
  }

  public function reversoRetiro(string $tipoDoc, string $documento, string $monto, int|string $departamento, int|string $ciudad, int $transactionId, int $sequenceId): array
  {
    return $this->ejecutarTransaccion([
      'transactionType' => self::TRANSACTION_TYPE_REVERSO,
      'amountTran' => $monto,
      'state' => $departamento,
      'city' => $this->normalizarCiudadParaApi($ciudad),
      'identificationType' => $tipoDoc,
      'identification' => $documento,
    ], $transactionId, $sequenceId);
  }

  public function reversoRetiroFalloLocal(string $tipoDoc, string $documento, string $monto, int|string $departamento, int|string $ciudad, int $transactionId, int $sequenceId): array
  {
    $resultado = $this->ejecutarTransaccion([
      'transactionType' => self::TRANSACTION_TYPE_REVERSO,
      'amountTran' => $monto,
      'state' => $departamento,
      'city' => $this->normalizarCiudadParaApi($ciudad),
      'identificationType' => $tipoDoc,
      'identification' => $documento,
    ], $transactionId, $sequenceId);

    if (
      empty($resultado) ||
      (isset($resultado['responseCode']) && $resultado['responseCode'] == false) ||
      isset($resultado['error'])
    ) {
      PagosRecaudosLogger::critical('Fallo en reverso. Accion manual requerida.', [
        'operation' => 'api_asopagos',
        'stage' => 'reverso_fallo_local',
        'reverso' => $resultado,
        'monto' => $monto,
        'tipoDoc' => $tipoDoc,
        'documento' => $documento,
        'departamento' => $departamento,
        'ciudad' => $ciudad,
      ]);

      return [
        'status' => 'fallo_sin_reverso',
        'error' => $resultado['error'] ?? 'Error desconocido',
        'reverso' => $resultado,
      ];
    }

    return [
      'status' => 'fallo_con_reverso',
      'error' => $resultado['error'] ?? 'Error desconocido',
      'reverso' => $resultado,
    ];
  }

  private function decodificarJson($response): array
  {
    $data = $response->json();

    return is_array($data) ? $data : [];
  }

  private function normalizarRespuestaError(array $data, array $payload, ?int $status = null, ?string $fallback = null, ?string $body = null): array
  {
    return array_merge($data, [
      'error' => $this->extraerMensajeError($data, $fallback),
      'responseCode' => $data['responseCode'] ?? false,
      'status' => $status,
      'body' => $body,
      'transactionId' => $data['transactionId'] ?? ($payload['transactionId'] ?? null),
      'sequenceId' => $data['sequenceId'] ?? ($payload['sequenceId'] ?? null),
      'authorizationRspCode' => $data['authorizationRspCode'] ?? null,
      'errorID' => $data['errorID'] ?? null,
      'transport_error' => false,
      'is_uncertain' => $this->debeMarcarEstadoIncierto($payload, $status, $data),
      'error_category' => $this->determinarCategoriaError($status, $data),
    ]);
  }

  private function extraerMensajeError(array $data, ?string $fallback = null): string
  {
    $additionalData = is_array($data['additionalData'] ?? null) ? $data['additionalData'] : [];
    $candidatos = [
      $data['error_description'] ?? null,
      $data['message'] ?? null,
      $data['error'] ?? null,
      $additionalData['validationError'] ?? null,
      $additionalData['errorMessage'] ?? null,
      $additionalData['errorMesssage'] ?? null,
      $data['errorID'] ?? null,
      $fallback,
    ];

    foreach ($candidatos as $valor) {
      if (is_string($valor) && trim($valor) !== '') {
        return trim($valor);
      }
    }

    return 'Error desconocido';
  }

  private function normalizarErrorTransporte(Throwable $exception, array $payload, bool $isUncertain, ?string $fallback = null): array
  {
    return [
      'error' => $this->mensajeErrorTransporte($exception, $fallback),
      'responseCode' => false,
      'status' => null,
      'body' => null,
      'transactionId' => $payload['transactionId'] ?? null,
      'sequenceId' => $payload['sequenceId'] ?? null,
      'authorizationRspCode' => null,
      'errorID' => null,
      'transport_error' => true,
      'is_uncertain' => $isUncertain,
      'error_category' => $this->esTimeoutThrowable($exception) ? 'timeout' : 'transport_error',
    ];
  }

  private function debeIntentarReverso(array $resultado): bool
  {
    return ($resultado['is_uncertain'] ?? false) === true
      && ! empty($resultado['transactionId'])
      && ! empty($resultado['sequenceId']);
  }

  private function esTransaccionMonetaria(array $payload): bool
  {
    return in_array($payload['transactionType'] ?? null, [
      self::TRANSACTION_TYPE_RETIRO,
      self::TRANSACTION_TYPE_REVERSO,
    ], true);
  }

  private function debeMarcarEstadoIncierto(array $payload, ?int $status, array $data): bool
  {
    if (! $this->esTransaccionMonetaria($payload)) {
      return false;
    }

    if (array_key_exists('responseCode', $data)) {
      return false;
    }

    if ($status === null) {
      return false;
    }

    return $status >= 500 || $status === 408;
  }

  private function determinarCategoriaError(?int $status, array $data): ?string
  {
    if (array_key_exists('responseCode', $data)) {
      return 'provider_reject';
    }

    if ($status === 408) {
      return 'timeout';
    }

    if ($status !== null && $status >= 500) {
      return 'server_error';
    }

    if ($status !== null && $status >= 400) {
      return 'client_error';
    }

    return null;
  }

  private function mensajeErrorTransporte(Throwable $exception, ?string $fallback = null): string
  {
    if ($this->esTimeoutThrowable($exception)) {
      return 'El servicio de pagos no respondio a tiempo. Intente nuevamente mas tarde.';
    }

    return $fallback ?? 'No fue posible comunicarse con el servicio de pagos. Intente nuevamente mas tarde.';
  }

  private function esTimeoutThrowable(Throwable $exception): bool
  {
    $mensaje = strtolower($exception->getMessage());

    return str_contains($mensaje, 'curl error 28')
      || str_contains($mensaje, 'operation timed out')
      || str_contains($mensaje, 'timed out');
  }

  private function tokenTimeoutSeconds(): int
  {
    return (int) config('apiAsopagos.token_timeout_seconds', 30);
  }

  private function transactionTimeoutSeconds(): int
  {
    return (int) config('apiAsopagos.transaction_timeout_seconds', 30);
  }

  private function tokenRetryAttempts(): int
  {
    return (int) config('apiAsopagos.token_retry_attempts', 1);
  }

  private function tokenRetrySleepMs(): int
  {
    return (int) config('apiAsopagos.token_retry_sleep_ms', 200);
  }

  private function normalizarCiudadParaApi(int|string $ciudad): string
  {
    $codigo = trim((string) $ciudad);

    return self::CITY_CODE_OVERRIDES[$codigo] ?? $codigo;
  }

  private function logApi(string $event, array $context = [], string $level = 'info'): void
  {
    $context = $this->sanitizeContext($context);

    Log::channel(self::API_LOG_CHANNEL)->{$level}($event, array_filter([
      'module' => 'pagos_recaudos',
      'request_id' => request()?->attributes->get('pagos_recaudos_request_id'),
      'route' => request()?->route()?->getName(),
      'method' => request()?->method(),
      'event' => $event,
      'context' => $context,
    ], fn($value) => $value !== null));
  }

  //Ajustes de la generacion del log de archivo paralelo para evitar incluir datos sensibles o demasiado verbosos.
  private function logApiResponseArchive(string $event, array $context = [], string $level = 'info'): void
  {
    try {
      $context = $this->sanitizeContext($context);
      $entry = $this->buildArchiveEntry($event, $context);

      Log::channel(self::API_RESPONSE_ARCHIVE_CHANNEL)->{$level}(
        $this->buildArchiveMessage($entry),
        $entry
      );
    } catch (Throwable) {
      // Este archivo paralelo es solo auxiliar y nunca debe afectar la operacion.
    }
  }

  private function buildArchiveEntry(string $event, array $context): array
  {
    $payload = is_array($context['payload'] ?? null) ? $context['payload'] : [];
    $response = is_array($context['response'] ?? null) ? $context['response'] : [];
    $direction = $this->resolveArchiveDirection($event);
    $action = $this->resolveTransactionName($context['transactionType'] ?? null);
    $transmissionDateTime = $direction === 'solicitud'
      ? ($payload['transmissionDateTime'] ?? null)
      : ($response['transmissionDateTime'] ?? ($payload['transmissionDateTime'] ?? null));
    $entry = [
      'proceso' => [
        'modulo' => 'pagos_recaudos',
        'accion' => $action,
        'direccion' => $direction,
        'resultado' => $direction === 'solicitud'
          ? 'enviada'
          : $this->resolveArchiveResult($context, $response),
      ],
      'trazabilidad' => [
        'request_id' => request()?->attributes->get('pagos_recaudos_request_id'),
        'route' => request()?->route()?->getName(),
        'method' => request()?->method(),
      ],
      'transaccion' => [
        'transaction_id' => $context['transactionId'] ?? ($response['transactionId'] ?? $payload['transactionId'] ?? null),
        'sequence_id' => $context['sequenceId'] ?? ($response['sequenceId'] ?? $payload['sequenceId'] ?? null),
        'fecha_transmision' => $transmissionDateTime,
        'duration_ms' => $context['duration_ms'] ?? null,
      ],
      'solicitud' => [
        'tipo_identificacion' => $payload['identificationType'] ?? null,
        'identificacion' => $payload['identification'] ?? null,
        'valor' => $payload['amountTran'] ?? null,
        'departamento' => $payload['state'] ?? null,
        'ciudad' => $payload['city'] ?? null,
      ],
    ];

    if ($direction === 'solicitud') {
      return $this->filterArchiveNulls($entry);
    }

    $entry['respuesta'] = [
      'http_status' => $context['http_status'] ?? null,
      'response_code' => array_key_exists('responseCode', $response) ? (bool) $response['responseCode'] : null,
      'authorization_code' => $response['authorizationRspCode'] ?? null,
      'error_id' => $response['errorID'] ?? ($response['errorId'] ?? null),
      'mensaje' => $this->resolveArchiveMessage($response),
      'saldo' => $response['additionalData']['saldo'] ?? null,
    ];

    return $this->filterArchiveNulls($entry);
  }

  private function buildArchiveMessage(array $entry): string
  {
    $parts = [
      'Cajasan',
      $entry['proceso']['accion'] ?? 'transaccion',
      $entry['proceso']['direccion'] ?? 'evento',
    ];

    if (! empty($entry['proceso']['resultado'])) {
      $parts[] = $entry['proceso']['resultado'];
    }

    return implode(' ', $parts);
  }

  private function resolveArchiveDirection(string $event): string
  {
    if (str_ends_with($event, '.request')) {
      return 'solicitud';
    }

    if (str_ends_with($event, '.response')) {
      return 'respuesta';
    }

    return 'evento';
  }

  private function resolveTransactionName(?string $transactionType): string
  {
    return match ((string) $transactionType) {
      self::TRANSACTION_TYPE_CONSULTA => 'consulta_saldo',
      self::TRANSACTION_TYPE_RETIRO => 'retiro',
      self::TRANSACTION_TYPE_REVERSO => 'reverso',
      default => 'transaccion',
    };
  }

  private function resolveArchiveResult(array $context, array $response): string
  {
    $httpStatus = $context['http_status'] ?? null;

    if (($response['responseCode'] ?? null) === true) {
      return 'ok';
    }

    if (($response['responseCode'] ?? null) === false) {
      return 'rechazada';
    }

    if (is_numeric($httpStatus) && (int) $httpStatus >= 500) {
      return 'error_http';
    }

    if (is_numeric($httpStatus) && (int) $httpStatus >= 400) {
      return 'error_cliente';
    }

    return 'sin_respuesta_clara';
  }

  private function resolveArchiveMessage(array $response): ?string
  {
    $message = $this->extraerMensajeError($response, null);

    if ($message !== 'Error desconocido') {
      return $message;
    }

    if (($response['responseCode'] ?? null) === true) {
      return 'Transaccion aprobada';
    }

    return null;
  }

  private function filterArchiveNulls(array $context): array
  {
    foreach ($context as $key => $value) {
      if (is_array($value)) {
        $value = $this->filterArchiveNulls($value);
      }

      if ($value === null || $value === '' || $value === []) {
        unset($context[$key]);
        continue;
      }

      $context[$key] = $value;
    }

    return $context;
  }

  private function sanitizeContext(array $context): array
  {
    foreach ($context as $key => $value) {
      if (is_array($value)) {
        $context[$key] = $this->sanitizeContext($value);
        continue;
      }

      if ($this->isSensitiveKey((string) $key)) {
        $context[$key] = '[redacted]';
      }
    }

    return $context;
  }

  private function isSensitiveKey(string $key): bool
  {
    $normalized = strtolower(trim($key));
    if (in_array($normalized, self::API_SENSITIVE_KEYS, true)) {
      return true;
    }

    return str_contains($normalized, 'token')
      || str_contains($normalized, 'password')
      || str_contains($normalized, 'secret');
  }
}
