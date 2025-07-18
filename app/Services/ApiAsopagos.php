<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiAsopagos
{
    public function obtenerToken()
    {
        $tokenCacheKey = config('apiAsopagos.token_cache_key');

        // Si ya hay un token válido en caché
        if (Cache::has($tokenCacheKey)) {
            return Cache::get($tokenCacheKey);
        }

        $response = Http::asForm()->post(config('apiAsopagos.token_url'), [
            'username'      => config('apiAsopagos.credentials.username'),
            'password'      => config('apiAsopagos.credentials.password'),
            'grant_type'    => 'password',
            'client_id'     => config('apiAsopagos.credentials.client_id'),
            'client_secret' => config('apiAsopagos.credentials.client_secret'),
            'scope'         => config('apiAsopagos.credentials.scope'),
        ]);

        if ($response->successful()) {
            $token = $response->json()['access_token'] ?? null;

            if ($token) {
                Cache::put($tokenCacheKey, $token, now()->addMinutes(config('apiAsopagos.token_cache_minutes')));
                return $token;
            }
        }

        Log::error('No se pudo obtener el token de Asopagos', [
                'status'  => $response->status(),
                'response' => $response->body()
        ]);

        return null;
    } 

    private function ejecutarTransaccion(array $datos)
    {
        $token = $this->obtenerToken();
        if (!$token) return null;

        $payload = array_merge([
            'partnerId'            => config('apiAsopagos.partner_id'),
            'originId'             => config('apiAsopagos.origin_id'),
            'clientId'             => config('apiAsopagos.client_id_value'),
            'sequenceId'           => now()->timestamp, //<-- mirar este dato como se calcularia
            'transactionId'        => now()->timestamp, //<-- mirar este dato como se calcularia
            'currencyCode'         => '170',
            'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
            'businessLine'         => '03',
            'user'                 => config('apiAsopagos.credentials.username'),
            'password'             => config('apiAsopagos.credentials.password'),
        ], $datos);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(config('apiAsopagos.base_url'), $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error al ejecutar transacción con Asopagos', [
                'status'  => $response->status(),
                'payload' => $payload,
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('Excepción en llamada a Asopagos', ['error' => $e->getMessage()]);
        }

        return null;
    }

    public function consultarSaldo(string $tipoDoc, string $documento, string $departamento, string $ciudad)
    {
        return $this->ejecutarTransaccion([
            'transactionType'    => '10',
            'state'              => $departamento,
            'city'               => $ciudad,
            'identificationType' => $tipoDoc,
            'identification'     => $documento,
        ]);
    }

    public function retirar(string $tipoDoc, string $documento, float $monto, string $departamento, string $ciudad)
    {
        return $this->ejecutarTransaccion([
            'transactionType'    => '01',
            'amountTran'         => $monto,
            'state'              => $departamento,
            'city'               => $ciudad,
            'identificationType' => $tipoDoc,
            'identification'     => $documento,
        ]);
    }

    public function reversoRetiro(string $tipoDoc, string $documento, float $monto, string $departamento, string $ciudad)
    {
        return $this->ejecutarTransaccion([
            'transactionType'    => '03',
            'amountTran'         => $monto,
            'state'              => $departamento,
            'city'               => $ciudad,
            'identificationType' => $tipoDoc,
            'identification'     => $documento,
        ]);
    }
}
