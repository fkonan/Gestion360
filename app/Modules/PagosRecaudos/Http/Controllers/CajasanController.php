<?php

namespace App\Modules\PagosRecaudos\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\LOGTRANS\ConDetCarguePagRec;
use App\Models\LOGTRANS\ConDetPagoRecaudo;
use App\Models\LOGTRANS\PerPersonas;
use App\Modules\PagosRecaudos\Services\Cajasan\ApiAsopagos;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoConsultaService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CajasanController extends Controller
{
  public function index(Request $request)
  {
    try {
      $cajaActiva = $request->attributes->get('caja_activa');

      if (!$cajaActiva || !isset($cajaActiva[0])) {
        Log::error('Caja activa no encontrada en el request');
        return sweetAlert(
          'No existe caja activa',
          'error',
          null,
          'No es posible acceder al módulo sin que el usuario tenga una caja activa asociada.'
        );
      }

      return view('pagosRecaudos.cajasan.index', compact('cajaActiva'));
    } catch (Exception $e) {
      Log::error('Error en index: ' . $e->getMessage());
      return redirect()->route('home')->with('error', 'Error al cargar la página');
    }
  }

  public function consultar(Request $request, ApiAsopagos $apiAsopagos, PagoConsultaService $consultaService)
  {
    $identificacion = trim($request->identificacion);

    if (empty($identificacion)) {
      return toastModal('La identificación es requerida.', 'danger');
    }

    $resultado = $consultaService->consultarSaldo($identificacion, $request->caja_activa_id, $apiAsopagos);

    if ($resultado['error']) {
      return toastModal($resultado['message'], 'danger');
    }

    return response()->json([
      'success' => true,
      'uuid'    => $resultado['uuid'],
      'html'    => view('pagosRecaudos.cajasan.pagosDisponibles', [
        'clienteData' => $resultado['clienteData'],
        'respuesta'   => $resultado['respuesta'],
        'uuid'        => $resultado['uuid']
      ])->render()
    ]);
  }

  public function validarInformacion(string $uuid)
  {
    $datosCifrados = Cache::get("pago:{$uuid}");

    if (!$datosCifrados) {
      return response()->json([
        'error' => true,
        'message' => "Sesión expirada, consulte nuevamente"
      ]);;
    }

    // Descifrar los datos
    $data = Crypt::decrypt($datosCifrados);

    $clienteData = $data['clienteData'];
    $respuesta   = $data['respuesta'];

    return view("pagosRecaudos.cajasan.validarPago", compact('clienteData', 'respuesta', 'uuid'));
  }


  public function pagar(Request $request, ApiAsopagos $apiAsopagos, PagoService $pagoService)
  {
    $resultado = $pagoService->pagar($request->uuid, $request->telefono, $apiAsopagos);

    //Caso de fallo
    if ($resultado['error']) {
      switch ($resultado['type']) {
        case 'rollback-fail':
          return sweetAlert(
            $resultado['message'],
            'error',
            null,
            'Ha ocurrido un error crítico en el pago.
                        Por favor guarde este código y contacte con mesa de ayuda: <b>' . ($resultado['extra'] ?? 'N/A') . '</b>'
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

        default:
          return sweetAlert('Ocurrió un error inesperado en el pago.', 'error');
      }
    }

    // Caso de éxito
    return view('pagosRecaudos.cajasan.confirmacionPago', [
      'cajaActiva'    => $resultado['cajaActiva'],
      'comprobante'   => $resultado['comprobante'],
      'idPagoDetalle' => $resultado['idPagoDetalle']
    ]);
  }

  /**
   * Generar recibo PDF
   */
  public function generarRecibo($IdDetallePago)
  {
    try {
      //Datos para generar el recibo
      $detallePago = ConDetCarguePagRec::findOrFail($IdDetallePago);
      $detalleComprobante = ConDetPagoRecaudo::where("id_det_carpagyrec", $IdDetallePago)->first();

      $agencia = PerPersonas::where("codigo", $detallePago->codagencia)->value("nomsucursal");

      $usuarioAgencia = PerPersonas::findOrFail($detallePago->usrcreacion);
      $nombreCompleto = $usuarioAgencia->pnombre . ' ' . $usuarioAgencia->snombre . ' ' . $usuarioAgencia->papellido . ' ' . $usuarioAgencia->sapellido;

      // Validar y separar fecha y hora
      if (!$detallePago->feccreacion) {
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
        'valor' => '$' . number_format($detallePago->valortotal, 0, ',', '.') . '=',
        'valor_total' => '$' . number_format($detallePago->valortotal, 0, ',', '.') . '=',
        'usuario_impresion' => $nombreCompleto,
        'fecha_impresion' => $fechaPago,
        'hora_impresion' => $horaPago
      ];

      $pdf = PDF::loadView('pagosRecaudos.cajasan.recibo', $data)
        ->setPaper([0, 0, 80, 210], 'portrait')
        ->setOptions([
          'dpi' => 203,
          'defaultFont' => 'DejaVu Sans',
          'isHtml5ParserEnabled' => true,
          'isRemoteEnabled' => false,
        ]);

      return $pdf->stream('recibo-cajasan.pdf');
    } catch (Exception $e) {
      Log::error('Error al generar recibo: ' . $e->getMessage());
      return redirect()->back()->with('error', 'Error al generar el recibo');
    }
  }
}
