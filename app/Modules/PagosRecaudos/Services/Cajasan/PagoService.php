<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PagoService
{
    // Estados de transacciones
    private const ESTADO_PAGADO = 'P';

    private const ESTADO_ANULADO = 'A';

    public function __construct(
        private CargueService $cargueService,
        private DetallePagoService $detallePagoService,
        private ComprobanteService $comprobanteService,
        private CajaTurnoDocService $cajaTurnoDocService,
        private PagoProcesoLockService $lockService
    ) {}

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

        $executionLock = $this->lockService->acquirePaymentExecutionLock($uuid);
        if (! $executionLock) {
            PagosRecaudosLogger::warning('Pago bloqueado por ejecucion concurrente del mismo uuid', [
                'operation' => 'pago',
                'uuid' => $uuid,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            return [
                'error' => true,
                'type' => 'payment-in-progress',
                'message' => 'Ya existe un pago en proceso. Espere unos segundos e intente nuevamente.',
            ];
        }

        $datosCifrados = Cache::get("pago:{$uuid}");
        if (! $datosCifrados) {
            PagosRecaudosLogger::warning('Pago rechazado por cache expirada o inexistente', [
                'operation' => 'pago',
                'uuid' => $uuid,
                'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
            ]);

            $this->lockService->release($executionLock);

            return [
                'error' => true,
                'type' => 'expired',
                'message' => 'Sesion expirada, consulte nuevamente.',
            ];
        }

        try {
            $data = Crypt::decrypt($datosCifrados);

            if (
                ! is_array($data) ||
                ! isset($data['clienteData'], $data['respuesta'], $data['cajaActiva'])
            ) {
                return [
                    'error' => true,
                    'type' => 'expired',
                'message' => 'La sesion ha expirado. Consulte nuevamente.',
                ];
            }

            $userId = UsuarioService::obtenerUserId();

            if (! $this->contextoPagoValido($data, $cajaActivaActual, $userId)) {
                PagosRecaudosLogger::warning('Intento de procesar pago fuera del contexto autorizado', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'caja_activa_id' => $cajaActivaActual->id ?? null,
                ]);

                return [
                    'error' => true,
                    'type' => 'invalid-context',
                    'message' => 'La informacion del pago ya no es valida. Consulte nuevamente.',
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
                    'message' => 'El pago no esta disponible en este momento. Intente nuevamente mas tarde.',
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

            $sucursalCajaActiva = isset($cajaActiva->idsucursal) ? (int) $cajaActiva->idsucursal : null;
            $centroCosto = $sucursalCajaActiva ? EmpleadoService::codigoCentroCostoPorSucursal($sucursalCajaActiva) : null;

            if (! $centroCosto) {
                throw new Exception('No se pudo obtener el centro de costo de la caja activa.');
            }

            // 2. Crear cargue y detalle
            $cargue = $this->cargueService->obtenerOCrear($cajaActiva, $userId);
            $detallePago = $this->detallePagoService->crearCargueDetalle($cargue->id, $clienteData, $respuesta, $cajaActiva, $userId);
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
                $estadoIncierto = ($pagoResponse['is_uncertain'] ?? false) === true;

                PagosRecaudosLogger::warning('Pago no exitoso, se inicia manejo de fallo', [
                    'operation' => 'pago',
                    'uuid' => $uuid,
                    'id_pago_detalle' => $idPagoDetalle,
                    'response_code' => $pagoResponse['responseCode'] ?? null,
                    'status' => $pagoResponse['status'] ?? null,
                    'error' => $pagoResponse['error'] ?? null,
                    'is_uncertain' => $estadoIncierto,
                    'provider_error_detail' => $this->extraerMensajeProveedor($pagoResponse),
                    'duration_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                ]);

                $this->manejarPagoFallido($pagoResponse, $detallePago, $clienteData, $respuesta);

                if (isset($pagoResponse['reverso']['responseCode']) && $pagoResponse['reverso']['responseCode'] == false) {
                    return [
                        'error' => true,
                        'type' => 'rollback-fail',
                        'message' => 'El pago no se pudo realizar.',
                        'extra' => $idPagoDetalle,
                    ];
                }

                if ($estadoIncierto) {
                    return [
                        'error' => true,
                        'type' => ReversoService::requiere($pagoResponse) ? 'rollback-ok' : 'rollback-fail',
                        'message' => 'El pago no se pudo realizar. Intente nuevamente mas tarde.',
                        'extra' => $pagoResponse['authorizationRspCode'] ?? ($pagoResponse['reverso']['authorizationRspCode'] ?? null),
                    ];
                }

                return [
                    'error' => true,
                    'type' => 'rollback-ok',
                    'message' => 'El pago no se pudo realizar. Verifique la informacion e intente nuevamente.',
                ];
            }

            // 4. Crear comprobantes y caja turno documento
            try {
                $resultado = $this->lockService->runSequenceCriticalSection(function () use (
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
                    return DB::connection('oracle')->transaction(function () use (
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
                        $codigo = $pagoResponse['authorizationRspCode'];
                        $detallePago->update([
                            'estado' => PagoService::ESTADO_PAGADO,
                            'nro_interno' => $codigo,
                        ]);

                        $saldo = $respuesta['additionalData']['saldo'] ?? 0;
                        $comprobante = $this->comprobanteService->obtenerOCrear($saldo, $cajaActiva, $userId);
                        $grupoBloque = $this->comprobanteService->obtenerGrupoBloque($comprobante->id);
                        $bloque = $grupoBloque->bloque;
                        $grupo = $grupoBloque->grupo;

                        $this->comprobanteService->crearDetalleComprobante(
                            $comprobante->id,
                            $idPagoDetalle,
                            $saldo,
                            $clienteData,
                            $cajaActiva,
                            $telefono,
                            $userId
                        );

                        $this->comprobanteService->crearAuxComprobante($comprobante->id, 'D', $saldo, $clienteData, $cajaActiva, $bloque, $grupo, $userId, $centroCosto);
                        $this->comprobanteService->crearAuxComprobante($comprobante->id, 'C', $saldo, $clienteData, $cajaActiva, $bloque, $grupo, $userId, $centroCosto);
                        $this->cajaTurnoDocService->crear($comprobante->id, $saldo, $cajaActiva, $userId);

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

                if ($runtime->shouldMockProvider()) {
                    return [
                        'error' => true,
                        'type' => 'rollback-fail',
                        'message' => 'El pago no se pudo realizar.',
                        'extra' => $detallePago->id,
                    ];
                }

                $resp = $this->manejoFalloTransaccion($codigo, $detallePago, $apiAsopagos, $clienteData, $respuesta, $e);

                $reversoExitoso = isset($resp['reverso']['responseCode']) && $resp['reverso']['responseCode'] == true;

                return [
                    'error' => true,
                    'type' => $reversoExitoso ? 'rollback-ok' : 'rollback-fail',
                    'message' => 'El pago no se pudo realizar.',
                    'extra' => $detallePago->id,
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
                'message' => 'No fue posible procesar el pago. Intente nuevamente mas tarde.',
            ];
        } finally {
            $this->lockService->release($executionLock ?? null);
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
        $resp = $this->lockService->runSequenceCriticalSection(function () use (
            $codigo,
            $detallePago,
            $apiAsopagos,
            $clienteData,
            $respuesta
        ) {
            $detallePago->update([
                'estado' => self::ESTADO_ANULADO,
                'nro_interno' => $codigo,
            ]);

            $resp = $apiAsopagos->reversoRetiroFalloLocal(
                $clienteData['tipoIdentificacion'],
                $clienteData['identificacion'],
                $respuesta['additionalData']['saldo'],
                $clienteData['departamento'],
                $clienteData['municipio'],
                $detallePago->id,
                $detallePago->id
            );

            if (isset($resp['reverso']) && is_array($resp['reverso'])) {
                ReversoService::crear($resp['reverso'], $codigo, $this->crearContextoReverso($clienteData, $respuesta));
            }

            return $resp;
        });

        PagosRecaudosLogger::error('Fallo en el proceso de pago. Se ejecuto reverso automatico en la API.', [
            'operation' => 'pago',
            'cliente_identificacion' => $clienteData['identificacion'] ?? 'No disponible',
            'respuesta_reverso' => $resp ?? 'Sin respuesta',
        ]);

        return $resp;
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
    private function manejarPagoFallido(array $pagoResponse, ConDetCarguePagRec $detallePago, array $clienteData, array $respuesta): void
    {
        try {
            $this->lockService->runSequenceCriticalSection(function () use ($pagoResponse, $detallePago, $clienteData, $respuesta) {
                if (ReversoService::requiere($pagoResponse)) {
                    ReversoService::crear(
                        $pagoResponse['reverso'],
                        $pagoResponse['authorizationRspCode'] ?? null,
                        $this->crearContextoReverso($clienteData, $respuesta)
                    );
                }

                $detallePago->update([
                    'estado' => self::ESTADO_ANULADO,
                    'nro_interno' => $pagoResponse['authorizationRspCode'] ?? ($pagoResponse['reverso']['authorizationRspCode'] ?? null),
                ]);
            });
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al manejar pago fallido', $e, [
                'operation' => 'pago',
                'id_pago_detalle' => $detallePago->id ?? null,
                'authorization_code' => $pagoResponse['authorizationRspCode'] ?? ($pagoResponse['reverso']['authorizationRspCode'] ?? null),
            ]);
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

           /*  return $apiAsopagos->retirar(
                $clienteData['tipoIdentificacion'],
                $clienteData['identificacion'],
                $respuesta['additionalData']['saldo'],
                68, // Departamento fijo para Cajasan
                68001, // Municipio fijo para Cajasan
                $idPagoDetalle,
                $idPagoDetalle
            ); */

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

            return ['error' => 'Error de comunicacion con la API', 'responseCode' => false];
        }
    }

    private function contextoPagoValido(array $data, object $cajaActivaActual, ?int $usuarioActual = null): bool
    {
        $contextoPago = $data['contextoPago'] ?? [];
        if (isset($contextoPago['usuario_id'], $contextoPago['caja_turno_id'], $contextoPago['sucursal_id'])) {
            $usuarioActual ??= UsuarioService::obtenerUserId();

            return (int) $contextoPago['usuario_id'] === (int) $usuarioActual
                && (int) $contextoPago['caja_turno_id'] === (int) ($cajaActivaActual->id ?? 0)
                && (int) $contextoPago['sucursal_id'] === (int) ($cajaActivaActual->idsucursal ?? 0);
        }

        $cajaActivaCache = $data['cajaActiva'] ?? null;

        return is_object($cajaActivaCache)
            && (int) ($cajaActivaCache->id ?? 0) === (int) ($cajaActivaActual->id ?? 0)
            && (int) ($cajaActivaCache->idsucursal ?? 0) === (int) ($cajaActivaActual->idsucursal ?? 0);
    }

    private function extraerMensajeProveedor(array $respuesta, string $fallback = 'Error en proveedor'): string
    {
        $additionalData = is_array($respuesta['additionalData'] ?? null) ? $respuesta['additionalData'] : [];
        $candidatos = [
            $respuesta['message'] ?? null,
            $respuesta['error'] ?? null,
            $additionalData['validationError'] ?? null,
            $additionalData['errorMessage'] ?? null,
            $additionalData['errorMesssage'] ?? null,
            $respuesta['errorID'] ?? null,
            $fallback,
        ];

        foreach ($candidatos as $valor) {
            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return $fallback;
    }

    private function crearContextoReverso(array $clienteData, array $respuesta): array
    {
        return [
            'identification_type' => $clienteData['tipoIdentificacion'] ?? null,
            'identification' => isset($clienteData['identificacion']) ? (string) $clienteData['identificacion'] : null,
            'amount_tran' => isset($respuesta['additionalData']['saldo']) ? (string) $respuesta['additionalData']['saldo'] : null,
            'state_code' => isset($clienteData['departamento']) ? (string) $clienteData['departamento'] : null,
            'city_code' => isset($clienteData['municipio']) ? (string) $clienteData['municipio'] : null,
        ];
    }
}
