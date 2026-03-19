<?php

namespace App\Modules\PagosRecaudos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Services\Cajasan\ApiAsopagos;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoHistorialService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoConsultaService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoService;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajasanController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cajaActiva = $request->attributes->get('caja_activa');

            if (! $cajaActiva || ! isset($cajaActiva[0])) {
                PagosRecaudosLogger::error('Caja activa no encontrada al cargar index del modulo', [
                    'operation' => 'index',
                ]);

                return sweetAlert(
                    'No existe caja activa',
                    'error',
                    null,
                    'No es posible acceder al módulo sin que el usuario tenga una caja activa asociada.'
                );
            }

            return view('pagosrecaudos::cajasan.index', compact('cajaActiva'));
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar index del modulo', $e, [
                'operation' => 'index',
            ]);

            return redirect()->route('home')->with('error', 'Error al cargar la página');
        }
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos, PagoConsultaService $consultaService)
    {
        $identificacion = trim($request->identificacion);
        $cajaActivaRef = $this->obtenerCajaActiva($request);

        PagosRecaudosLogger::info('Inicio de consulta de pago convenio', [
            'operation' => 'consulta',
            'identificacion_cliente' => $identificacion,
            'caja_activa_ref' => $cajaActivaRef?->id,
        ]);

        if (empty($identificacion)) {
            return toastModal('La identificación es requerida.', 'danger');
        }

        if (empty($cajaActivaRef)) {
            return toastModal('No se encontro una caja activa valida.', 'danger');
        }

        $resultado = $consultaService->consultarSaldo($identificacion, $cajaActivaRef, $apiAsopagos);

        if ($resultado['error']) {
            return toastModal($resultado['message'], 'danger');
        }

        return response()->json([
            'success' => true,
            'uuid' => $resultado['uuid'],
            'html' => view('pagosrecaudos::cajasan.pagosDisponibles', [
                'clienteData' => $resultado['clienteData'],
                'respuesta' => $resultado['respuesta'],
                'uuid' => $resultado['uuid'],
            ])->render(),
        ]);
    }

    public function historialHoy(Request $request, PagoHistorialService $historialService)
    {
        $startedAt = microtime(true);

        try {
            $cajaActiva = $this->obtenerCajaActiva($request);

            if (! $cajaActiva) {
                return sweetAlert(
                    'No existe caja activa',
                    'error',
                    null,
                    'No es posible consultar el historial sin una caja activa asociada.'
                );
            }

            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'estado' => $request->query('estado', 'todos'),
            ];

            $userIdStartedAt = microtime(true);
            $userId = UsuarioService::obtenerUserId();
            $userIdMs = PagosRecaudosLogger::elapsedMs($userIdStartedAt);

            $historialStartedAt = microtime(true);
            $pagos = $historialService->obtenerPagosDelDia($cajaActiva, $userId, $filters);
            $historialMs = PagosRecaudosLogger::elapsedMs($historialStartedAt);

            PagosRecaudosLogger::info('Pantalla de historial de pagos del dia cargada', [
                'operation' => 'historial_hoy',
                'usuario_id' => $userId,
                'caja_activa_id' => $cajaActiva->id ?? null,
                'duracion_user_id_ms' => $userIdMs,
                'duracion_historial_ms' => $historialMs,
                'duracion_total_ms' => PagosRecaudosLogger::elapsedMs($startedAt),
                'estado' => $filters['estado'],
                'q' => $filters['q'],
                'registros_pagina' => $pagos->count(),
                'pagina_actual' => $pagos->currentPage(),
                'hay_mas_paginas' => $pagos->hasMorePages(),
            ]);

            return view('pagosrecaudos::cajasan.historialHoy', [
                'cajaActiva' => $cajaActiva,
                'pagos' => $pagos,
                'filters' => $filters,
            ]);
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar historial de pagos del dia', $e, [
                'operation' => 'historial_hoy',
            ]);

            return redirect()->route('pagosConvenios.index')->with('error', 'Error al cargar el historial de pagos del dia.');
        }
    }

    public function validarInformacion(Request $request, string $uuid)
    {
        $cajaActiva = $this->obtenerCajaActiva($request);
        $data = $this->obtenerDatosPagoCacheados($uuid);

        if (! $cajaActiva || ! $data) {
            return response()->json([
                'error' => true,
                'message' => 'Sesión expirada, consulte nuevamente',
            ]);
        }

        if (! $this->contextoPagoValido($data, $cajaActiva)) {
            PagosRecaudosLogger::warning('Intento de validar pago fuera del contexto autorizado', [
                'operation' => 'validar_pago',
                'uuid' => $uuid,
                'caja_activa_id' => $cajaActiva->id ?? null,
            ]);

            Cache::forget("pago:{$uuid}");

            return response()->json([
                'error' => true,
                'message' => 'La sesiÃ³n de pago no corresponde a la caja activa actual. Consulte nuevamente.',
            ]);
        }

        $clienteData = $data['clienteData'];
        $respuesta = $data['respuesta'];

        return view('pagosrecaudos::cajasan.validarPago', compact('clienteData', 'respuesta', 'uuid'));
    }

    public function pagar(Request $request, ApiAsopagos $apiAsopagos, PagoService $pagoService)
    {
        $cajaActiva = $this->obtenerCajaActiva($request);

        if (! $cajaActiva) {
            return sweetAlert('No existe una caja activa valida para procesar el pago.', 'error');
        }

        $resultado = $pagoService->pagar($request->uuid, $request->telefono, $cajaActiva, $apiAsopagos);

        // Caso de fallo
        if ($resultado['error']) {
            switch ($resultado['type']) {
                case 'rollback-fail':
                    return sweetAlert(
                        $resultado['message'],
                        'error',
                        null,
                        'Ha ocurrido un error crítico en el pago.
                        Por favor guarde este código y contacte con mesa de ayuda: <b>'.($resultado['extra'] ?? 'N/A').'</b>'
                    );

                case 'rollback-ok':
                    return sweetAlert(
                        $resultado['message'],
                        'error',
                        null,
                        null
                    );

                case 'expired':
                    return sweetAlert($resultado['message'], 'error');

                case 'invalid-context':
                    return sweetAlert($resultado['message'], 'error');

                case 'readonly-not-supported':
                    return sweetAlert($resultado['message'], 'error');

                default:
                    return sweetAlert('Ocurrió un error inesperado en el pago.', 'error');
            }
        }

        // Caso de éxito
        return view('pagosrecaudos::cajasan.confirmacionPago', [
            'cajaActiva' => $resultado['cajaActiva'],
            'comprobante' => $resultado['comprobante'],
            'idPagoDetalle' => $resultado['idPagoDetalle'],
        ]);
    }

    /**
     * Generar recibo PDF
     */
    public function generarRecibo(Request $request, $IdDetallePago)
    {
        try {
            $cajaActiva = $this->obtenerCajaActiva($request);

            if (! $cajaActiva) {
                throw new Exception('No se encontrÃ³ una caja activa valida para generar el recibo.');
            }

            // Datos para generar el recibo
            $detallePago = ConDetCarguePagRec::where('id', $IdDetallePago)
                ->where('estborrado', 0)
                ->where('empcreacion', $cajaActiva->idsucursal)
                ->firstOrFail();

            $detalleComprobante = DB::connection('oracle')
                ->table('CON_DETALLEPAGORECAUDO as DPR')
                ->join('CON_COMPROBANTES as CP', 'DPR.CP_ID', '=', 'CP.ID')
                ->select('DPR.*')
                ->where('DPR.ID_DET_CARPAGYREC', $IdDetallePago)
                ->where('DPR.ESTBORRADO', 0)
                ->where('CP.ESTBORRADO', 0)
                ->where('CP.CT_ID', $cajaActiva->id)
                ->first();

            if (! $detalleComprobante) {
                throw new Exception('No se encontrÃ³ el detalle contable asociado al recibo.');
            }

            $agencia = PerPersonas::where('codigo', $detallePago->codagencia)->value('nomsucursal');

            $usuarioAgencia = PerPersonas::findOrFail($detallePago->usrcreacion);
            $nombreCompleto = $usuarioAgencia->pnombre.' '.$usuarioAgencia->snombre.' '.$usuarioAgencia->papellido.' '.$usuarioAgencia->sapellido;

            // Validar y separar fecha y hora
            if (! $detallePago->feccreacion) {
                throw new Exception('Fecha de creación no disponible');
            }

            $fechaHora = explode(' ', $detallePago->feccreacion);
            $fechaPago = $fechaHora[0] ?? '';
            $horaPago = $fechaHora[1] ?? '';

            $data = [
                'linea_atencion' => 'Línea de Atención al Cliente 018000114161-0180006444164',
                'vigencia' => 'Vigilado por Mintic',
                'convenio' => 'CONVENIO CAJASAN - PAGOS',
                'fecha' => $fechaPago,
                'hora' => $horaPago,
                'agencia' => $agencia,
                'ciudad' => strtoupper($detallePago->ciudad),
                'codigo' => $detallePago->nro_interno,
                'telefono' => $detalleComprobante->telefono,
                'principal' => $detallePago->clienteprincipal,
                'identificacion_principal' => $detallePago->iden_clienteprincipal,
                'pagado_a' => $detallePago->clienteprincipal,
                'identificacion_pagado' => $detallePago->iden_clienteprincipal,
                'concepto' => 'CONVENIO CAJASAN - PAGOS',
                'valor' => '$'.number_format($detallePago->valortotal, 0, ',', '.').'=',
                'valor_total' => '$'.number_format($detallePago->valortotal, 0, ',', '.').'=',
                'usuario_impresion' => $nombreCompleto,
                'fecha_impresion' => $fechaPago,
                'hora_impresion' => $horaPago,
            ];

            $pdf = PDF::loadView('pagosrecaudos::cajasan.recibo', $data)
                ->setPaper([0, 0, 80, 210], 'portrait')
                ->setOptions([
                    'dpi' => 203,
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                ]);

            return $pdf->stream('recibo-cajasan.pdf');
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al generar recibo', $e, [
                'operation' => 'recibo',
                'id_pago_detalle' => $IdDetallePago,
            ]);
            Log::error('Error al generar recibo: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al generar el recibo');
        }
    }

    private function obtenerCajaActiva(Request $request): ?object
    {
        $cajaActiva = $request->attributes->get('caja_activa');

        if (is_object($cajaActiva) && method_exists($cajaActiva, 'first')) {
            return $cajaActiva->first();
        }

        if (is_array($cajaActiva)) {
            return $cajaActiva[0] ?? null;
        }

        if (is_object($cajaActiva) && isset($cajaActiva[0])) {
            return $cajaActiva[0];
        }

        return is_object($cajaActiva) ? $cajaActiva : null;
    }

    private function obtenerDatosPagoCacheados(string $uuid): ?array
    {
        $datosCifrados = Cache::get("pago:{$uuid}");

        if (! $datosCifrados) {
            return null;
        }

        $data = Crypt::decrypt($datosCifrados);

        return is_array($data) ? $data : null;
    }

    private function contextoPagoValido(array $data, object $cajaActiva): bool
    {
        try {
            $usuarioActual = UsuarioService::obtenerUserId();
        } catch (Exception) {
            return false;
        }

        $contextoPago = $data['contextoPago'] ?? [];
        if (isset($contextoPago['usuario_id'], $contextoPago['caja_turno_id'], $contextoPago['sucursal_id'])) {
            return (int) $contextoPago['usuario_id'] === (int) $usuarioActual
                && (int) $contextoPago['caja_turno_id'] === (int) ($cajaActiva->id ?? 0)
                && (int) $contextoPago['sucursal_id'] === (int) ($cajaActiva->idsucursal ?? 0);
        }

        $cajaActivaCache = $data['cajaActiva'] ?? null;

        return is_object($cajaActivaCache)
            && (int) ($cajaActivaCache->id ?? 0) === (int) ($cajaActiva->id ?? 0)
            && (int) ($cajaActivaCache->idsucursal ?? 0) === (int) ($cajaActiva->idsucursal ?? 0);
    }
}
