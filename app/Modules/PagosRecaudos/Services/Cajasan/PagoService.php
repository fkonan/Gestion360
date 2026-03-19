<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoService
{
    // Estados de transacciones
    private const ESTADO_PAGADO = 'P';

    private const ESTADO_ANULADO = 'A';

    // Configuraciones de comprobantes
    public function pagar(string $uuid, string $telefono, object $cajaActivaActual, ApiAsopagos $apiAsopagos): array
    {
        $startedAt = microtime(true);
        $runtime = new AsopagosRuntimeConfig;

        PagosRecaudosLogger::info('Inicio de procesamiento de pago', [
            'operation' => 'pago',
            'uuid' => $uuid,
        ] + $runtime->context());

        if ($runtime->isDangerousMockConfiguration()) {
            PagosRecaudosLogger::warning('Proveedor mock activo con persistencia real en PagosRecaudos', [
                'operation' => 'pago',
                'uuid' => $uuid,
            ] + $runtime->context());
        }

        $datosCifrados = Cache::get("pago:{$uuid}");
        if (! $datosCifrados) {
            PagosRecaudosLogger::warning('Pago rechazado por cache expirada o inexistente', [
                'operation' => 'pago',
                'uuid' => $uuid,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return [
                'error' => true,
                'type' => 'expired',
                'message' => 'Sesión expirada, consulte nuevamente.',
            ];
        }

        // Descifrar datos
        $data = Crypt::decrypt($datosCifrados);

        try {
            if (
                ! is_array($data) ||
                ! isset($data['clienteData'], $data['respuesta'], $data['cajaActiva'])
            ) {
                return [
                    'error' => true,
                    'type' => 'expired',
                    'message' => 'SesiÃ³n expirada, consulte nuevamente.',
                ];
            }

            if (! $this->contextoPagoValido($data, $cajaActivaActual)) {
                PagosRecaudosLogger::warning('Intento de procesar pago fuera del contexto autorizado', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'caja_activa_id' => $cajaActivaActual->id ?? null,
                ]);

                return [
                    'error' => true,
                    'type' => 'invalid-context',
                    'message' => 'La sesiÃ³n de pago no corresponde a la caja activa actual. Consulte nuevamente.',
                ];
            }

            if (! $runtime->shouldUseRealPersistence()) {
                PagosRecaudosLogger::warning('Pago bloqueado por persistence_mode readonly no implementado aun', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                ] + $runtime->context());

                return [
                    'error' => true,
                    'type' => 'readonly-not-supported',
                    'message' => 'El modo readonly aun no esta habilitado para pagos. Cambie persistence_mode a real para continuar.',
                ];
            }

            $clienteData = $data['clienteData'];
            $respuesta = $data['respuesta'];
            $cajaActiva = $data['cajaActiva'];

            // 1. Validar saldo
            $saldo = $respuesta['additionalData']['saldo'] ?? 0;
            if ($saldo <= 0) {
                throw new Exception('Saldo insuficiente para procesar el pago');
            }

            $userId = UsuarioService::obtenerUserId();
            $documentoUsuario = Auth::user()?->persona?->PerNumDoc;
            $centroCosto = $documentoUsuario ? EmpleadoService::codigoCentroCosto($documentoUsuario) : null;

            if (! $centroCosto) {
                throw new Exception('No se pudo obtener el centro de costo del usuario actual.');
            }

            // 2. Crear cargue y detalle
            $cargueService = new CargueService;
            $cargue = $cargueService->obtenerOCrear($cajaActiva, $userId);

            $detalleService = new DetallePagoService;
            $detallePago = $detalleService->crearCargueDetalle($cargue->id, $clienteData, $respuesta, $cajaActiva, $userId);
            $idPagoDetalle = $detallePago->id;

            PagosRecaudosLogger::info('Registros base del pago creados', [
                'operation' => 'pago',
                'uuid' => $uuid,
                'id_cargue' => $cargue->id ?? null,
                'id_pago_detalle' => $idPagoDetalle,
                'identificacion_cliente' => $clienteData['identificacion'] ?? null,
                'saldo' => $saldo,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            // 3. Ejecutar pago en la API
            $pagoResponse = $this->procesarPago($apiAsopagos, $clienteData, $respuesta, $idPagoDetalle);

            PagosRecaudosLogger::info('Respuesta de pago recibida', [
                'operation' => 'pago',
                'uuid' => $uuid,
                'id_pago_detalle' => $idPagoDetalle,
                'response_code' => $pagoResponse['responseCode'] ?? null,
                'authorization_code' => $pagoResponse['authorizationRspCode'] ?? null,
                'status' => $pagoResponse['status'] ?? null,
                'error' => $pagoResponse['error'] ?? null,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            if (! $this->esPagoExitoso($pagoResponse)) {
                PagosRecaudosLogger::warning('Pago no exitoso, se inicia manejo de fallo', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'id_pago_detalle' => $idPagoDetalle,
                    'response_code' => $pagoResponse['responseCode'] ?? null,
                    'status' => $pagoResponse['status'] ?? null,
                    'error' => $pagoResponse['error'] ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                $this->manejarPagoFallido($pagoResponse, $detallePago);

                if (isset($pagoResponse['reverso']['responseCode']) && $pagoResponse['reverso']['responseCode'] == false) {
                    return [
                        'error' => true,
                        'type' => 'rollback-fail',
                        'message' => 'Ocurrió un error en el pago.',
                        'extra' => $pagoResponse['reverso']['authorizationRspCode'] ?? null,
                    ];
                }

                return [
                    'error' => true,
                    'type' => 'rollback-ok',
                    'message' => 'El pago fue rechazado por el proveedor.',
                ];
            }

            // 4. Crear comprobantes y caja turno documento
            try {
                $resultado = DB::connection('oracle')->transaction(function () use (
                    $cargue,
                    $pagoResponse,
                    $detallePago,
                    $clienteData,
                    $respuesta,
                    $cajaActiva,
                    $idPagoDetalle,
                    $telefono,
                    $userId,
                    $centroCosto
                ) {

                    // Actualizar detalle cargue a pagado
                    $codigo = $pagoResponse['authorizationRspCode'];
                    $detallePago->update([
                        'estado' => PagoService::ESTADO_PAGADO,
                        'nro_interno' => $codigo,
                    ]);

                    $saldo = $respuesta['additionalData']['saldo'] ?? 0;
                    $comprobanteService = new ComprobanteService;
                    $comprobante = $comprobanteService->obtenerOCrear($saldo, $cajaActiva, $userId);

                    // Obtener la secuencia del bloque y grupo
                    $grupoBloque = $comprobanteService->obtenerGrupoBloque($comprobante->id);
                    $bloque = $grupoBloque->bloque;
                    $grupo = $grupoBloque->grupo;

                    // Detalle comprobante
                    $comprobanteService->crearDetalleComprobante(
                        $comprobante->id,
                        $idPagoDetalle,
                        $saldo,
                        $clienteData,
                        $cajaActiva,
                        $telefono,
                        $userId
                    );

                    $comprobanteService->crearAuxComprobante($comprobante->id, 'D', $saldo, $clienteData, $cajaActiva, $bloque, $grupo, $userId, $centroCosto);
                    $comprobanteService->crearAuxComprobante($comprobante->id, 'C', $saldo, $clienteData, $cajaActiva, $bloque, $grupo, $userId, $centroCosto);

                    $cajaTurno = new CajaTurnoDocService;
                    $cajaTurno->crear($comprobante->id, $saldo, $cajaActiva, $userId);

                    // Actualziar cargue a pagado
                    $cargue->update([
                        'estado' => PagoService::ESTADO_PAGADO,
                    ]);

                    return [
                        'error' => false,
                        'cajaActiva' => $cajaActiva,
                        'comprobante' => $comprobante,
                        'idPagoDetalle' => $detallePago->id,
                    ];
                });

                PagosRecaudosLogger::info('Pago persistido correctamente en contabilidad local', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'id_pago_detalle' => $resultado['idPagoDetalle'] ?? null,
                    'comprobante_id' => $resultado['comprobante']->id ?? null,
                    'comprobante' => $resultado['comprobante']->comprobante ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                return $resultado;
            } catch (Exception $e) {
                // Reversar pago en la API
                PagosRecaudosLogger::exception('Error en persistencia local del pago, se intentara reverso', $e, [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'id_pago_detalle' => $detallePago->id ?? null,
                    'authorization_code' => $pagoResponse['authorizationRspCode'] ?? null,
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);
                $codigo = $pagoResponse['authorizationRspCode'];

                // Respuesta de prueba
                if ($runtime->shouldMockProvider()) {
                    return [
                        'error' => true,
                        'type' => false ? 'rollback-ok' : 'rollback-fail',
                        'message' => false
                          ? 'No fue posible completar el pago en este momento. Intente más tarde.'
                          : 'Ocurrió un error en el pago.',
                        'extra' => 645321,
                    ];
                }

                $resp = $this->manejoFalloTransaccion($codigo, $detallePago, $apiAsopagos, $clienteData, $respuesta, $e);

                return [
                    'error' => true,
                    'type' => $resp['responseCode'] ? 'rollback-ok' : 'rollback-fail',
                    'message' => $resp['responseCode']
                      ? 'No fue posible completar el pago en este momento. Intente más tarde.'
                      : 'Ocurrió un error en el pago.',
                    'extra' => $codigo,
                ];
            }
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error no controlado en procesamiento de pago', $e, [
                'operation' => 'pago',
                'uuid' => $uuid,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return [
                'error' => true,
                'type' => 'internal',
                'message' => 'Error interno al realizar el pago.',
            ];
        } finally {
            DB::disconnect('oracle');
            Cache::forget("pago:{$uuid}");
        }
    }

    /**
     * Manejor transaccion fallida por causa interna
     */
    private function manejoFalloTransaccion(
        $codigo,
        ConDetCarguePagRec $detallePago,
        ApiAsopagos $apiAsopagos,
        array $clienteData,
        array $respuesta,
        Exception $e
    )
    {
        $detallePago->update([
            'estado' => self::ESTADO_ANULADO,
            'nro_interno' => $codigo,
        ]);

        // Lanzar reverso en la API
        $resp = $apiAsopagos->reversoRetiroFalloLocal(
            $clienteData['tipoIdentificacion'],
            $clienteData['identificacion'],
            $respuesta['additionalData']['saldo'],
            $clienteData['departamento'],
            $clienteData['municipio'],
            $detallePago->id,
            $detallePago->id
        );

        // Registrar reverso
        $reverso = new ReversoService;
        $reverso::crear($resp['reverso'], $codigo);

        PagosRecaudosLogger::error('Fallo en el proceso de pago. Se ejecuto reverso automatico en la API.', [
            'operation' => 'pago',
            'cliente_identificacion' => $clienteData['identificacion'] ?? 'No disponible',
            'respuesta_reverso' => $resp ?? 'Sin respuesta',
        ]);

        Log::error('❌ Fallo en el proceso de pago. Se ejecutó reverso automático en la API.', [
            'cliente_identificacion' => $clienteData['identificacion'] ?? 'No disponible',
            'respuesta_reverso' => $resp ?? 'Sin respuesta',
            /* 'detalle_error'          => $e->getMessage(), */
        ]);

        return $resp['reverso'];
    }

    /**
     * Verificar si el pago fue exitoso
     */
    private function esPagoExitoso(array $pagoResponse): bool
    {
        return empty($pagoResponse['error']) && ($pagoResponse['responseCode'] ?? false) === true;
    }

    /**
     * Manejar pago fallido
     */
    private function manejarPagoFallido(array $pagoResponse, ConDetCarguePagRec $detallePago): void
    {
        try {
            $reverso = new ReversoService;

            // Procesar reverso si aplica
            if ($reverso::requiere($pagoResponse)) {
                $reverso::crear($pagoResponse['reverso'], $pagoResponse['authorizationRspCode'] ?? $pagoResponse['reverso']['authorizationRspCode']);
            }

            // Cambiar estado a anulado
            $detallePago->update([
                'estado' => self::ESTADO_ANULADO,
                'nro_interno' => $pagoResponse['reverso']['authorizationRspCode'] ?? null,
            ]);
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al manejar pago fallido', $e, [
                'operation' => 'pago',
                'id_pago_detalle' => $detallePago->id ?? null,
                'authorization_code' => $pagoResponse['authorizationRspCode'] ?? ($pagoResponse['reverso']['authorizationRspCode'] ?? null),
            ]);

            Log::error('Error al manejar pago fallido: '.$e->getMessage());
        }
    }

    /**
     * Procesar pago con la API
     */
    private function procesarPago(ApiAsopagos $apiAsopagos, array $clienteData, array $respuesta, int $idPagoDetalle): array
    {
        try {
            $runtime = new AsopagosRuntimeConfig;
            if ($runtime->shouldMockProvider()) {
                $mock = new AsopagosMockService;
                PagosRecaudosLogger::debug('Pago usando respuesta mock', [
                    'operation' => 'pago',
                    'id_pago_detalle' => $idPagoDetalle,
                    'identificacion_cliente' => $clienteData['identificacion'] ?? null,
                ] + $runtime->context());

                return $mock->pago($idPagoDetalle, $clienteData, $runtime);
            }

            // Descomenta para usar la API real:
            return $apiAsopagos->retirar(
                $clienteData['tipoIdentificacion'],
                $clienteData['identificacion'],
                $respuesta['additionalData']['saldo'],
                $clienteData['departamento'],
                $clienteData['municipio'],
                $idPagoDetalle,
                $idPagoDetalle
            );
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al retirar en proveedor', $e, [
                'operation' => 'pago',
                'id_pago_detalle' => $idPagoDetalle,
                'identificacion_cliente' => $clienteData['identificacion'] ?? null,
            ]);

            Log::error('Error en API retirar: '.$e->getMessage());

            return ['error' => 'Error de comunicación con la API', 'responseCode' => false];
        }
    }

    private function contextoPagoValido(array $data, object $cajaActivaActual): bool
    {
        $contextoPago = $data['contextoPago'] ?? [];
        if (isset($contextoPago['usuario_id'], $contextoPago['caja_turno_id'], $contextoPago['sucursal_id'])) {
            return (int) $contextoPago['usuario_id'] === (int) UsuarioService::obtenerUserId()
                && (int) $contextoPago['caja_turno_id'] === (int) ($cajaActivaActual->id ?? 0)
                && (int) $contextoPago['sucursal_id'] === (int) ($cajaActivaActual->idsucursal ?? 0);
        }

        $cajaActivaCache = $data['cajaActiva'] ?? null;

        return is_object($cajaActivaCache)
            && (int) ($cajaActivaCache->id ?? 0) === (int) ($cajaActivaActual->id ?? 0)
            && (int) ($cajaActivaCache->idsucursal ?? 0) === (int) ($cajaActivaActual->idsucursal ?? 0);
    }
}
