<?php


namespace App\Services;

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
        $response = Http::post(config('apiReportes.base_url')."/auth", [
            'username' => config('apiReportes.username'),
            'password' => config('apiReportes.password'),
        ]);

        if ($response->successful()) {
            $token = $response->json()['token'];

            if (!$token) {
                Log::error('No se pudo obtener el token de ApiReportes');
                return null;
            }

            // Guarda el token en caché por 60 minutos
            Cache::put($tokenCacheKey, $token, now()->addMinutes(60));

            return $token;
        }

        return null;
    }
    
    public function obtenerReporte(array $params)
    {
        $token = $this->obtenerToken();

        if (!$token) {
            return null;
        }

        // Validar que los parámetros obligatorios existan
        foreach (['idReporte', 'fechaInicio', 'fechaFin'] as $obligatorio) {
            if (empty($params[$obligatorio])) {
                return null;
            }
        }

        $payload = array_filter([
            'paramFechaInicio'  => $params['fechaInicio'] ?? null,
            'paramFechaFin'     => $params['fechaFin'] ?? null,
            'paramAgencia'      => $params['agencia'] ?? null,
            'paramTipoFiltro'   => $params['tipoFiltro'] ?? null,
            'paramValorFiltro'  => $params['valorFiltro'] ?? null,
        ], fn($value) => !is_null($value));

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(config('apiReportes.base_url') . "/reporte/{$params['idReporte']}", $payload);

        return $response->successful()
            ? $response->json()['data']
            : null;
    }
}
