<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiReportes
{
    protected $baseUrl = 'https://integration.copetran.com.co/autogestion';
    
    public function obtenerToken()
    {
        $response = Http::post("{$this->baseUrl}/auth", [
            'username' => 'autogestionCopetran',
            'password' => '2025*autogestion$',
        ]);

        if ($response->successful()) {
            return $response->json()['token'];
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
