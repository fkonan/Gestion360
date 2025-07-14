<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiReportes
{
    protected $baseUrl = 'https://integration.copetran.com.co/autogestion';
    protected $tokenCacheKey = 'api_reportes_token';

    public function obtenerToken()
    {
        // Si ya hay un token válido en caché
        if (Cache::has($this->tokenCacheKey)) {
            return Cache::get($this->tokenCacheKey);
        }

        // Si no, se pide a la API
        $response = Http::post("{$this->baseUrl}/auth", [
            'username' => 'autogestionCopetran',
            'password' => '2025*autogestion$',
        ]);

        if ($response->successful()) {
            $token = $response->json()['token'];

            if (!$token) {
                Log::error('No se pudo obtener el token de ApiReportes');
                return null;
            }

            // Guarda el token en caché por 60 minutos
            Cache::put($this->tokenCacheKey, $token, now()->addMinutes(60));

            return $token;
        }

        return null;
    }
    
    public function obtenerReporte($idReporte, $fechaInicio, $fechaFin, $agencia = null)
    {
        $token = $this->obtenerToken();

        if (!$token) {
            return null;
        }

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/reporte/{$idReporte}", [
                'paramFechaInicio' => $fechaInicio,
                'paramFechaFin' => $fechaFin,
                'paramAgencia' => $agencia,
            ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        return null;
    }
}
