<?php

namespace App\Services\Cajasan;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiAsopagos
{
  public function obtenerToken()
  {
    $tokenCacheKey = config('apiAsopagos.token_cache_key');

    // Valida si hay token cacheado
    if (Cache::has($tokenCacheKey)) {
      return ['token' => Cache::get($tokenCacheKey)];
    }

    $response = Http::asForm()
      ->timeout(28)
      ->retry(3, 200, function ($exception, $request) {
        Log::warning('🔄 REINTENTO - Token Asopagos falló', [
          'exception_type' => get_class($exception),
          'timestamp' => now()
        ]);

        return $exception instanceof \Illuminate\Http\Client\ConnectionException
          || $exception instanceof \Illuminate\Http\Client\RequestException;
      })
      ->post(config('apiAsopagos.token_url'), [
        'username'      => config('apiAsopagos.credentials.auth_username'),
        'password'      => config('apiAsopagos.credentials.auth_password'),
        'grant_type'    => 'password',
        'client_id'     => config('apiAsopagos.credentials.client_id'),
        'client_secret' => config('apiAsopagos.credentials.client_secret'),
        'scope'         => config('apiAsopagos.credentials.scope'),
      ]);

    try {
      if ($response->successful()) {
        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if ($token) {
          //Se cachea el token
          Cache::put($tokenCacheKey, $token, now()->addMinutes(config('apiAsopagos.token_cache_minutes')));
          return ['token' => $token];
        }

        Log::error('Respuesta sin access_token', ['response' => $data]);
        return ['error' => 'Token no recibido'];
      } else {
        Log::error('No se pudo obtener el token de Asopagos', [
          'status'  => $response->status(),
          'response' => $response->body()
        ]);

        return ['error' => $response->json()['error_description'] ?? 'Error desconocido'];
      }
    } catch (\Exception $e) {
      Log::error('Excepción al obtener el token Asopagos', [
        'exception' => $e->getMessage(),
      ]);

      return ['error' => 'Excepción: ' . $e->getMessage()];
    }
  }

  private function ejecutarTransaccion(array $datos, ?int $transactionId = null, ?int $sequenceId = null)
  {
    $tokenResponse = $this->obtenerToken();
    if (isset($tokenResponse['error'])) {
      throw new \Exception('Error al obtener token: ' . $tokenResponse['error']);
    }

    $token = $tokenResponse['token'];

    $payload = array_merge([
      'partnerId'            => config('apiAsopagos.partner_id'),
      'originId'             => config('apiAsopagos.origin_id'),
      'clientId'             => config('apiAsopagos.client_id_value'),
      'sequenceId'           => $sequenceId,
      'transactionId'        => $transactionId,
      'currencyCode'         => '170',
      'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
      'businessLine'         => '03',
      'user'                 => config('apiAsopagos.credentials.username'),
      'password'             => config('apiAsopagos.credentials.password'),
    ], $datos);

    $response = Http::withToken($token)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->timeout(28)
      ->post(config('apiAsopagos.base_url'), $payload);

    try {
      $data = $response->json();

      //verificar que si existan datos en la respuesta de ASOPAGOS
      if (!$data || !is_array($data)) {
        Log::error('Respuesta inválida de Asopagos', [
          'status' => $response->status(),
          'body' => $response->body()
        ]);

        return [
          'error' => 'Respuesta inválida del servidor Asopagos',
          'transactionId' => $payload['transactionId'],
          'sequenceId' => $payload['sequenceId'],
        ];
      }

      if ($response->successful()) {
        //respuesta de ASOPAGOS es TRUE
        if (isset($data['responseCode']) && $data['responseCode'] == true) {
          return array_merge(['sequenceId' => $payload['sequenceId']], $data);
        }

        //respuesta de ASOPAGOS es FALSE
        Log::error('Error al ejecutar transacción con Asopagos', [
          'status'   => $response->status(),
          'payload' => collect($payload)->except(['user', 'password'])->all(),
          'response' => $data,
        ]);

        return array_merge(['sequenceId' => $payload['sequenceId'], 'error' => true], $data);
      } else {
        return [
          'error' => 'Respuesta HTTP no exitosa',
          'status' => $response->status(),
          'body' => $response->body(),
          'transactionId' => $payload['transactionId'],
          'sequenceId' => $payload['sequenceId'],
        ];
      }
    } catch (\Exception $e) {
      Log::error('Excepción al ejecutar transacción con Asopagos', [
        'payload' => collect($payload)->except(['user', 'password'])->all(),
        'exception' => $e->getMessage(),
      ]);

      return [
        'error' => 'Excepción: ' . $e->getMessage(),
        'transactionId' => $payload['transactionId'],
        'sequenceId' => $payload['sequenceId'],
      ];
    }
  }

  public function consultarSaldo(string $tipoDoc, int $documento, int $departamento, int $ciudad)
  {
    return $this->ejecutarTransaccion([
      'transactionType'    => '10',
      'currencyCode'       => null,
      'state'              => $departamento,
      'city'               => $ciudad,
      'identificationType' => $tipoDoc,
      'identification'     => $documento,
    ]);
  }

  public function retirar(string $tipoDoc, int $documento, string $monto, int $departamento, int $ciudad, int $transactionId, int $sequenceId)
  {
    $resultado = $this->ejecutarTransaccion([
      'transactionType'    => '01',
      'amountTran'         => $monto,
      'state'              => $departamento,
      'city'               => $ciudad,
      'identificationType' => $tipoDoc,
      'identification'     => $documento,
    ], $transactionId, $sequenceId);

    $error = $resultado['error'] ?? null;

    // Detecta si el error es por timeout
    $esTimeout = is_string($error) && (
      str_contains($error, 'cURL error 28') ||
      str_contains($error, 'Operation timed out')
    );

    // Si hay un timeout se inicia el proceso de reverso
    if ($esTimeout  && isset($resultado['transactionId'], $resultado['sequenceId'])) {
      Log::warning('Fallo en retiro. Iniciando reverso...', [
        'error'         => $resultado['error'] ?? 'Error desconocido',
        'transactionId' => $resultado['transactionId'],
        'sequenceId'    => $resultado['sequenceId'],
      ]);

      // Llamar al reverso
      $reverso = $this->reversoRetiro(
        $tipoDoc,
        $documento,
        $monto,
        $departamento,
        $ciudad,
        $resultado['transactionId'],
        $resultado['sequenceId']
      );

      // Verifica si el reverso también falló
      if (empty($reverso) || (isset($reverso['responseCode']) && $reverso['responseCode'] == false) || isset($reverso['error'])) {
        Log::critical('⚠️ Fallo reverso tras timeout en retiro. Acción manual requerida.', [
          'retiro_error'   => $resultado['error'] ?? $resultado['errorID'] ?? 'Error desconocido',
          'reverso_error'  => $reverso['error'] ?? $reverso['errorID'],
          'transactionId'  => $resultado['transactionId'],
          'sequenceId'     => $resultado['sequenceId'],
          'monto'          => $monto,
          'tipoDoc'        => $tipoDoc,
          'documento'      => $documento,
          'departamento'   => $departamento,
          'ciudad'         => $ciudad,
        ]);

        return [
          'status' => 'fallo_timeout_sin_reverso',
          'error' => $resultado['error'] ?? 'Error desconocido',
          'reverso' => $reverso,
        ];
      }

      return [
        'status' => 'fallo_timeout_con_reverso',
        'error' => $resultado['error'] ?? 'Error desconocido',
        'reverso' => $reverso,
      ];
    }
    return $resultado;
  }

  public function reversoRetiro(string $tipoDoc, int $documento, string $monto, int $departamento, int $ciudad, int $transactionId, int $sequenceId)
  {
    return $this->ejecutarTransaccion([
      'transactionType'    => '03',
      'amountTran'         => $monto,
      'state'              => $departamento,
      'city'               => $ciudad,
      'identificationType' => $tipoDoc,
      'identification'     => $documento,
    ], $transactionId, $sequenceId);
  }

  public function reversoRetiroFalloLocal(string $tipoDoc, int $documento, string $monto, int $departamento, int $ciudad, int $transactionId, int $sequenceId)
  {
    $resultado = $this->ejecutarTransaccion([
      'transactionType'    => '03',
      'amountTran'         => $monto,
      'state'              => $departamento,
      'city'               => $ciudad,
      'identificationType' => $tipoDoc,
      'identification'     => $documento,
    ], $transactionId, $sequenceId);

    // Verifica si el reverso también falló
    if (empty($resultado) || (isset($resultado['responseCode']) && $resultado['responseCode'] == false) || isset($reverso['error'])) {
      Log::critical('⚠️ Fallo en reverso. Acción manual requerida.', [
        'reverso'        => $resultado,
        'monto'          => $monto,
        'tipoDoc'        => $tipoDoc,
        'documento'      => $documento,
        'departamento'   => $departamento,
        'ciudad'         => $ciudad,
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
}
