<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\IntentoOperacion;
use App\Modules\Sarlaft\Models\IntentoPersona;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntentoOperacionService
{
    /**
     * Procesa un intento reportado via Push (el sistema externo nos llama).
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrarPush(array $datos, SistemaConsumidor $sistema, string $ip): IntentoOperacion
    {
        return DB::connection('mysql-sarlaft')->transaction(function () use ($datos, $sistema, $ip): IntentoOperacion {
            $intento = IntentoOperacion::create([
                'sistema_id' => $sistema->id,
                'modo_integracion' => 'push',
                'tipo_operacion' => $datos['tipo_operacion'],
                'referencia_externa' => $datos['referencia_externa'] ?? null,
                'fecha_operacion' => $datos['fecha_operacion'],
                'origen' => $datos['origen'] ?? null,
                'destino' => $datos['destino'] ?? null,
                'monto' => $datos['monto'] ?? null,
                'moneda' => $datos['moneda'] ?? 'COP',
                'descripcion' => $datos['descripcion'] ?? null,
                'contexto' => $datos['contexto'] ?? null,
                'ip_origen' => $ip,
            ]);

            $this->registrarPersonas($intento, $datos['personas']);
            $this->generarAlertasPorPersonas($intento, $datos['personas']);

            return $intento->load(['personas', 'alertas']);
        });
    }

    /**
     * Ejecuta el proceso Pull para un sistema consumidor:
     * consulta su endpoint y registra los intentos encontrados.
     */
    public function ejecutarPull(SistemaConsumidor $sistema): int
    {
        if (! $sistema->pull_endpoint) {
            return 0;
        }

        try {
            $response = Http::withToken($sistema->pull_token ?? '')
                ->timeout(30)
                ->get($sistema->pull_endpoint, [
                    'fecha' => now()->subDay()->toDateString(),
                ]);

            if (! $response->successful()) {
                Log::warning('SARLAFT Pull: respuesta no exitosa', [
                    'sistema' => $sistema->codigo,
                    'status' => $response->status(),
                ]);

                return 0;
            }

            $intentos = $response->json('data') ?? $response->json() ?? [];
            $registrados = 0;

            foreach ($intentos as $item) {
                if (! is_array($item) || empty($item['personas'])) {
                    continue;
                }

                DB::connection('mysql-sarlaft')->transaction(function () use ($item, $sistema, &$registrados): void {
                    $intento = IntentoOperacion::create([
                        'sistema_id' => $sistema->id,
                        'modo_integracion' => 'pull',
                        'tipo_operacion' => $item['tipo_operacion'] ?? 'venta',
                        'referencia_externa' => $item['referencia_externa'] ?? null,
                        'fecha_operacion' => $item['fecha_operacion'] ?? now(),
                        'origen' => $item['origen'] ?? null,
                        'destino' => $item['destino'] ?? null,
                        'monto' => $item['monto'] ?? null,
                        'moneda' => $item['moneda'] ?? 'COP',
                        'descripcion' => $item['descripcion'] ?? null,
                        'contexto' => isset($item['contexto']) && is_array($item['contexto']) ? $item['contexto'] : null,
                        'ip_origen' => null,
                    ]);

                    $this->registrarPersonas($intento, $item['personas']);
                    $this->generarAlertasPorPersonas($intento, $item['personas']);
                    $registrados++;
                });
            }

            return $registrados;
        } catch (\Throwable $e) {
            Log::error('SARLAFT Pull: error al consultar sistema', [
                'sistema' => $sistema->codigo,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $personas
     */
    private function registrarPersonas(IntentoOperacion $intento, array $personas): void
    {
        foreach ($personas as $persona) {
            if (! is_array($persona)) {
                continue;
            }

            IntentoPersona::create([
                'intento_id' => $intento->id,
                'tipo_documento' => $persona['tipo_documento'] ?? '',
                'numero_documento' => $persona['numero_documento'] ?? '',
                'nombre' => $persona['nombre'] ?? null,
                'rol' => $persona['rol'] ?? 'cliente',
                'tipo_lista' => $persona['tipo_lista'] ?? 'vinculante',
                'lista_nombre' => $persona['lista_nombre'] ?? '',
                'detalle_coincidencia' => isset($persona['detalle_coincidencia']) && is_array($persona['detalle_coincidencia'])
                    ? $persona['detalle_coincidencia']
                    : null,
            ]);
        }
    }

    /**
     * Genera una alerta por cada persona que tuvo coincidencia en el intento.
     *
     * @param  array<int, array<string, mixed>>  $personas
     */
    private function generarAlertasPorPersonas(IntentoOperacion $intento, array $personas): void
    {
        foreach ($personas as $persona) {
            if (! is_array($persona)) {
                continue;
            }

            $nivelRiesgo = $persona['tipo_lista'] === 'vinculante' ? 'alto' : 'medio';

            Alerta::create([
                'consulta_id' => null,
                'intento_id' => $intento->id,
                'tipo' => 'intento_operacion_' . $intento->modo_integracion,
                'nivel_riesgo' => $nivelRiesgo,
                'estado' => 'pendiente',
                'datos_persona' => [
                    'tipo_documento' => $persona['tipo_documento'] ?? '',
                    'numero_documento' => $persona['numero_documento'] ?? '',
                    'nombre' => $persona['nombre'] ?? null,
                    'rol' => $persona['rol'] ?? null,
                ],
                'listas_coincidentes' => [
                    [
                        'tipo' => $persona['tipo_lista'] ?? '',
                        'nombre' => $persona['lista_nombre'] ?? '',
                        'detalle' => $persona['detalle_coincidencia'] ?? null,
                    ],
                ],
                'contexto_operacion' => [
                    'tipo_operacion' => $intento->tipo_operacion,
                    'referencia_externa' => $intento->referencia_externa,
                    'fecha_operacion' => $intento->fecha_operacion?->toIso8601String(),
                    'origen' => $intento->origen,
                    'destino' => $intento->destino,
                    'monto' => $intento->monto,
                    'moneda' => $intento->moneda,
                    'descripcion' => $intento->descripcion,
                    'sistema' => $intento->sistema?->nombre,
                    'modo_integracion' => $intento->modo_integracion,
                ],
            ]);
        }
    }
}
