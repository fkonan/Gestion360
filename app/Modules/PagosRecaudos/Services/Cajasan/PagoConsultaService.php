<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\GestionWeb\Models\GenMunicipios;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagoConsultaService
{
    /**
     * Consultar saldo disponible y preparar datos de cliente
     */
    public function consultarSaldo(string $identificacion, int|object $cajaActivaRef, ApiAsopagos $apiAsopagos): array
    {
        $startedAt = microtime(true);
        $runtime = new AsopagosRuntimeConfig;

        try {
            PagosRecaudosLogger::info('Inicio de consulta de saldo', [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $identificacion,
                'caja_activa_ref' => is_object($cajaActivaRef) ? ($cajaActivaRef->id ?? null) : $cajaActivaRef,
            ] + $runtime->context());

            if ($runtime->isDangerousMockConfiguration()) {
                PagosRecaudosLogger::warning('Proveedor mock activo con persistencia real en PagosRecaudos', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                ] + $runtime->context());
            }

            // 1. Validar cliente
            $cliente = PerPersonas::where('identificacion', $identificacion)
                ->where('estado', 'ACTIVO')
                ->where('estborrado', 0)
                ->first();
            if (! $cliente) {
                PagosRecaudosLogger::warning('Cliente no encontrado en consulta de saldo', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return ['error' => true, 'message' => 'Verifique el documento. Si es primer pago, registre la persona.'];
            }

            // 2. Obtener caja activa
            $cajaActiva = $this->resolverCajaActiva($cajaActivaRef);

            if (! $cajaActiva) {
                PagosRecaudosLogger::warning('No fue posible resolver la caja activa para la consulta', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);
                return ['error' => true, 'message' => 'No se encontró una caja activa válida.'];
            }

            // 3. Preparar datos cliente
            [$departamento, $municipio] = $this->resolverUbicacionCaja($cajaActiva);

            if ($departamento === null || $municipio === null) {
                PagosRecaudosLogger::warning('No fue posible resolver la ubicacion de la caja activa', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'caja_activa_id' => $cajaActiva->id ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);
                return ['error' => true, 'message' => 'No fue posible resolver la ubicacion de la caja activa.'];
            }

            // 4. Tipo de identificación
            $tiposDocumento = [
                1 => 'CC', // Cédula de Ciudadanía
                2 => 'TI', // Tarjeta de Identidad
                3 => 'NT', // NIT
                5 => 'CE', // Cédula de Extranjería
                6 => 'PA', // Pasaporte
                43 => 'RC', // Registro
                50 => 'PE', // Permiso especial permanencia
            ];

            if ($cliente) {
                $sigla = $tiposDocumento[$cliente->tipdocumento] ?? null;

                if (! $sigla) {
                    PagosRecaudosLogger::warning('Tipo de identificacion no valido para consulta de saldo', [
                        'operation' => 'consulta_saldo',
                        'identificacion_cliente' => $identificacion,
                        'tipo_documento' => $cliente->tipdocumento ?? null,
                        'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                    ]);

                    return [
                        'error' => true,
                        'message' => 'Tipo de identificación no válido.',
                    ];
                }
            }

            $clienteData = [
                'tipoIdentificacion' => $sigla,
                'identificacion' => $cliente->identificacion,
                'nombre' => $cliente->nombreCompleto(),
                'departamento' => $departamento,
                'municipio' => $municipio,
            ];

            // 4. Consultar API
            $respuesta = $this->consultarSaldoApi($apiAsopagos, $clienteData, $runtime);
            if ($this->tieneErrorRespuesta($respuesta)) {
                PagosRecaudosLogger::warning('Consulta de saldo finalizo con error en proveedor', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'provider_response_code' => $respuesta['responseCode'] ?? null,
                    'provider_error' => $respuesta['error'] ?? ($respuesta['additionalData']['errorMesssage'] ?? null),
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => $respuesta['additionalData']['errorMesssage'] ?? 'Error en la consulta.',
                ];
            }

            // 5. Validar saldo
            $saldo = $respuesta['additionalData']['saldo'] ?? 0;
            if ($saldo <= 0) {
                PagosRecaudosLogger::info('Consulta de saldo sin fondos disponibles', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $identificacion,
                    'saldo' => $saldo,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return [
                    'error' => true,
                    'message' => 'Fondos insuficientes: el cliente no tiene saldo disponible para completar la operación.',
                ];
            }

            // 6. Guardar en cache
            $uuid = Str::uuid()->toString();
            $contextoPago = $this->crearContextoPago($cajaActiva);
            $datosCifrados = Crypt::encrypt(compact('clienteData', 'respuesta', 'cajaActiva', 'contextoPago'));
            Cache::put("pago:{$uuid}", $datosCifrados, now()->addMinutes(10));

            PagosRecaudosLogger::info('Consulta de saldo completada y cacheada', [
                'operation' => 'consulta_saldo',
                'uuid' => $uuid,
                'identificacion_cliente' => $identificacion,
                'saldo' => $saldo,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return [
                'error' => false,
                'uuid' => $uuid,
                'clienteData' => $clienteData,
                'respuesta' => $respuesta,
            ];
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error no controlado en consulta de saldo', $e, [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $identificacion,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return ['error' => true, 'message' => 'Error en la consulta, inténtelo nuevamente más tarde'];
        }
    }

    private function resolverCajaActiva(int|object $cajaActivaRef): ?object
    {
        if (is_object($cajaActivaRef)) {
            return $cajaActivaRef;
        }

        return DB::connection('oracle')
            ->table('TES_CAJATURNOS as T')
            ->join('TES_CAJAS as CJ', 'T.CJ_ID', '=', 'CJ.ID')
            ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
            ->select('P.NOMSUCURSAL', 'P.id as idsucursal', 'T.*')
            ->where('T.ID', $cajaActivaRef)
            ->first();
    }

    private function resolverUbicacionCaja(object $cajaActiva): array
    {
        $sucursal = PerPersonas::where('id', $cajaActiva->idsucursal)
            ->where('estado', 'ACTIVO')
            ->where('estborrado', 0)
            ->firstOrFail();

        $municipio = GenMunicipios::findOrFail($sucursal->mu_id);

        return [(int) $municipio->do_codigo, (int) $municipio->codigo];
    }

    private function consultarSaldoApi(ApiAsopagos $apiAsopagos, array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        try {
            if ($runtime->shouldMockProvider()) {
                $mock = new AsopagosMockService;
                PagosRecaudosLogger::debug('Consulta de saldo usando respuesta mock', [
                    'operation' => 'consulta_saldo',
                    'identificacion_cliente' => $clienteData['identificacion'] ?? null,
                ] + $runtime->context());

                return $mock->consultaSaldo($clienteData, $runtime);
            }

            return $apiAsopagos->consultarSaldo(
                $clienteData['tipoIdentificacion'],
                $clienteData['identificacion'],
                $clienteData['departamento'],
                $clienteData['municipio']
            );
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al consultar saldo en proveedor', $e, [
                'operation' => 'consulta_saldo',
                'identificacion_cliente' => $clienteData['identificacion'] ?? null,
            ]);

            return ['error' => true, 'message' => 'Error de comunicación con la API'];
        }
    }

    private function tieneErrorRespuesta(array $respuesta): bool
    {
        return isset($respuesta['error']) ||
          ($respuesta['responseCode'] ?? false) === false ||
          ! isset($respuesta['additionalData']['saldo']);
    }

    private function crearContextoPago(object $cajaActiva): array
    {
        return [
            'usuario_id' => UsuarioService::obtenerUserId(),
            'caja_turno_id' => (int) ($cajaActiva->id ?? 0),
            'sucursal_id' => (int) ($cajaActiva->idsucursal ?? 0),
        ];
    }
}
