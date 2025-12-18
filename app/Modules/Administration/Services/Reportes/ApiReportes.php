<?php


namespace App\Modules\Administration\Services\Reportes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiReportes
{

  public function obtenerToken()
  {
    $tokenCacheKey = config('apiReportes.token_cache_key');

    // Si ya hay un token válido en caché
    if (Cache::has($tokenCacheKey)) {
      return Cache::get($tokenCacheKey);
    }

    // Si no, se pide a la API
    $response = Http::post(config('apiReportes.base_url') . "/auth", [
      'username' => config('apiReportes.username'),
      'password' => config('apiReportes.password'),
    ]);

    if ($response->successful()) {
      $token = $response->json()['token'];

      if (!$token) {
        Log::build([
          'driver' => 'daily',
          'path' => storage_path('logs/reportes/apiReportes.log'),
          'days' => 7,
        ])->info('No se pudo obtener el token de ApiReportes');

        return null;
      }

      // Guarda el token en caché por 60 minutos
      Cache::put($tokenCacheKey, $token, now()->addMinutes(60));

      return $token;
    }

    return null;
  }

  public function obtenerReporte(array $params, int $timeout = 120)
  {
    $token = $this->obtenerToken();

    if (!$token) {
      Log::build([
          'driver' => 'daily',
          'path' => storage_path('logs/reportes/apiReportes.log'),
          'days' => 7,
        ])->info('ApiReportes: Token no disponible');

      return null;
    }

    // Validar que los parámetros obligatorios existan
    foreach (['idReporte'] as $obligatorio) {
      if (empty($params[$obligatorio])) {
        Log::build([
          'driver' => 'daily',
          'path' => storage_path('logs/reportes/apiReportes.log'),
          'days' => 7,
        ])->info('ApiReportes: Falta parametro obligatorio', ['param' => $obligatorio]);

        return null;
      }
    }

    // Convertir parámetros dinámicamente a formato "paramXxx"
    $payload = [];

    foreach ($params as $key => $value) {
      if ($key === 'idReporte') {
        continue;
      }

      $payload['param' . ucfirst($key)] = $value;
    }

    $start = microtime(true);
    $response = Http::withToken($token)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->timeout($timeout)
      ->retry(2, 2000)
      ->post(config('apiReportes.base_url') . "/reporte/{$params['idReporte']}", $payload);

    $durationMs = round((microtime(true) - $start) * 1000, 2);

    if ($response->successful()) {
      return $response->json()['data'];
    }

    Log::build([
        'driver' => 'daily',
        'path' => storage_path('logs/reportes/apiReportes.log'),
        'days' => 7,
      ])->info('ApiReportes: error al obtener reporte', [
      'idReporte' => $params['idReporte'],
      'status' => $response->status(),
      'duration_ms' => $durationMs,
    ]);

    return null;
  }
}
