<?php

namespace App\Modules\PagosRecaudos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Services\Cajasan\ApiAsopagos;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoHistorialService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoConsultaService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoService;
use App\Modules\PagosRecaudos\Services\Cajasan\ReversoHistorialService;
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
            $sinCajaPorSuperAdmin = (bool) $request->attributes->get('caja_activa_bypass', false);

            if ((! $cajaActiva || ! isset($cajaActiva[0])) && ! $sinCajaPorSuperAdmin) {
                PagosRecaudosLogger::error('Caja activa no encontrada al cargar index del modulo', [
                    'operation' => 'index',
                ]);

                return sweetAlert(
                    'No existe caja activa',
                    'error',
                    null,
                    'No es posible acceder al modulo sin que el usuario tenga una caja activa asociada.'
                );
            }

            if ($sinCajaPorSuperAdmin && (! $cajaActiva || ! isset($cajaActiva[0]))) {
                $cajaActiva = collect();
            }

            return view('pagosrecaudos::cajasan.index', compact('cajaActiva', 'sinCajaPorSuperAdmin'));
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar index del modulo', $e, [
                'operation' => 'index',
            ]);

            return redirect()->route('home')->with('error', 'Error al cargar la pagina');
        }
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos, PagoConsultaService $consultaService)
    {
        $identificacion = strtoupper(trim((string) $request->identificacion));
        $cajaActivaRaw = $request->attributes->get('caja_activa');
        $cajaActivaRef = $this->obtenerCajaActiva($request);

        PagosRecaudosLogger::info('Inicio de consulta de pago convenio', [
            'operation' => 'consulta',
            'identificacion_cliente' => $identificacion,
            'caja_activa_raw_type' => is_object($cajaActivaRaw)
                ? $cajaActivaRaw::class
                : gettype($cajaActivaRaw),
            'caja_activa_raw_count' => is_object($cajaActivaRaw) && method_exists($cajaActivaRaw, 'count')
                ? $cajaActivaRaw->count()
                : (is_array($cajaActivaRaw) ? count($cajaActivaRaw) : null),
            'caja_activa_ref' => $cajaActivaRef?->id,
            'host' => gethostname(),
        ]);

        if (empty($identificacion)) {
            return toastModal('La identificacion es requerida.', 'danger');
        }

        if (! preg_match('/^[A-Z0-9]+$/', $identificacion)) {
            return toastModal('La identificacion solo puede contener letras y numeros.', 'danger');
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
        try {
            $cajaActiva = $this->obtenerCajaActiva($request);
            $sinCajaPorSuperAdmin = (bool) $request->attributes->get('caja_activa_bypass', false);

            if (! $cajaActiva && ! $sinCajaPorSuperAdmin) {
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

            $userId = UsuarioService::obtenerUserId();
            $pagos = $historialService->obtenerPagosDelDia($cajaActiva, $userId, $filters, $sinCajaPorSuperAdmin);

            return view('pagosrecaudos::cajasan.historialHoy', [
                'cajaActiva' => $cajaActiva,
                'pagos' => $pagos,
                'filters' => $filters,
                'sinCajaPorSuperAdmin' => $sinCajaPorSuperAdmin,
            ]);
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar historial de pagos del dia', $e, [
                'operation' => 'historial_hoy',
            ]);

            return redirect()->route('pagosConvenios.index')->with('error', 'Error al cargar el historial de pagos del dia.');
        }
    }

    public function historialReversos(Request $request, ReversoHistorialService $reversoHistorialService)
    {
        try {
            $cajaActiva = $this->obtenerCajaActiva($request);

            if (! $cajaActiva) {
                return sweetAlert(
                    'No existe caja activa',
                    'error',
                    null,
                    'No es posible consultar los reversos sin una caja activa asociada.'
                );
            }

            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'resultado' => $request->query('resultado', 'todos'),
            ];

            $reversos = $reversoHistorialService->obtenerReversos($filters);

            return view('pagosrecaudos::cajasan.historialReversos', [
                'cajaActiva' => $cajaActiva,
                'reversos' => $reversos,
                'filters' => $filters,
            ]);
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar historial de reversos', $e, [
                'operation' => 'historial_reversos',
            ]);

            return redirect()->route('pagosConvenios.index')->with('error', 'Error al cargar el historial de reversos.');
        }
    }


    public function exportarHistorialReversos(Request $request, ReversoHistorialService $reversoHistorialService)
    {
        try {
            $cajaActiva = $this->obtenerCajaActiva($request);

            if (! $cajaActiva) {
                return response()->json([
                    'errors' => ['general' => ['No existe una caja activa valida para exportar los reversos.']],
                ], 422);
            }

            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'resultado' => $request->query('resultado', 'todos'),
            ];

            $rows = $reversoHistorialService->obtenerReversosExportacion($filters);

            return response()->json([
                'total' => count($rows),
                'rows' => $rows,
            ]);
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al exportar historial de reversos', $e, [
                'operation' => 'historial_reversos_exportar',
            ]);

            return response()->json([
                'errors' => ['general' => ['Error al exportar el historial de reversos.']],
            ], 500);
        }
    }
    public function validarInformacion(Request $request, string $uuid)
    {
        $cajaActiva = $this->obtenerCajaActiva($request);
        $data = $this->obtenerDatosPagoCacheados($uuid);

        if (! $cajaActiva || ! $data) {
            return response()->json([
                'error' => true,
                'message' => 'La sesion ha expirado. Consulte nuevamente.',
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
                'message' => 'La informacion del pago ya no es valida. Consulte nuevamente.',
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
                        'Error inesperado en el pago',
                        'error',
                        null,
                        'El pago no se pudo realizar. Por favor comuniquese con mesa de ayuda e informe el numero de detalle: <b>'.($resultado['extra'] ?? 'N/A').'</b>.'
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

                case 'payment-in-progress':
                    return sweetAlert($resultado['message'], 'warning');

                case 'readonly-not-supported':
                    return sweetAlert($resultado['message'], 'error');

                default:
                    return sweetAlert('Ocurrio un error inesperado en el pago.', 'error');
            }
        }

        // Caso de exito
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
            $sinCajaPorSuperAdmin = (bool) $request->attributes->get('caja_activa_bypass', false);

            if (! $cajaActiva && ! $sinCajaPorSuperAdmin) {
                throw new Exception('No se encontro una caja activa valida para generar el recibo.');
            }

            // Datos para generar el recibo
            $detallePagoQuery = ConDetCarguePagRec::where('id', $IdDetallePago)
                ->where('estborrado', 0);

            if (! $sinCajaPorSuperAdmin) {
                $detallePagoQuery->where('empcreacion', $cajaActiva->idsucursal);
            }

            $detallePago = $detallePagoQuery->firstOrFail();

            $detalleComprobanteQuery = DB::connection('oracle')
                ->table('CON_DETALLEPAGORECAUDO as DPR')
                ->join('CON_COMPROBANTES as CP', 'DPR.CP_ID', '=', 'CP.ID')
                ->select('DPR.*')
                ->where('DPR.ID_DET_CARPAGYREC', $IdDetallePago)
                ->where('DPR.ESTBORRADO', 0)
                ->where('CP.ESTBORRADO', 0);

            if (! $sinCajaPorSuperAdmin) {
                $detalleComprobanteQuery->where('CP.CT_ID', $cajaActiva->id);
            }

            $detalleComprobante = $detalleComprobanteQuery->first();

            if (! $detalleComprobante) {
                throw new Exception('No se encontro el detalle contable asociado al recibo.');
            }

            $agencia = PerPersonas::where('codigo', $detallePago->codagencia)->value('nomsucursal');

            $usuarioAgencia = PerPersonas::findOrFail($detallePago->usrcreacion);
            $nombreCompleto = $usuarioAgencia->pnombre.' '.$usuarioAgencia->snombre.' '.$usuarioAgencia->papellido.' '.$usuarioAgencia->sapellido;

            // Validar y separar fecha y hora
            if (! $detallePago->feccreacion) {
                throw new Exception('Fecha de creacion no disponible');
            }

            $fechaHora = explode(' ', $detallePago->feccreacion);
            $fechaPago = $fechaHora[0] ?? '';
            $horaPago = $fechaHora[1] ?? '';

            $data = [
                'linea_atencion' => 'Linea de Atencion al Cliente 018000114161-0180006444164',
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

            $mmToPoints = 72 / 25.4;
            $paperWidthMm = 80;
            $paperHeightMm = 210;

            $pdf = PDF::loadView('pagosrecaudos::cajasan.recibo', $data)
                ->setPaper([0, 0, $paperWidthMm * $mmToPoints, $paperHeightMm * $mmToPoints], 'portrait')
                ->setOptions([
                    'dpi' => 72,
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
