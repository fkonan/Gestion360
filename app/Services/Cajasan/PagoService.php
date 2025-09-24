<?php

namespace App\Services\Cajasan;

use App\Models\LOGTRANS\ConDetCarguePagRec;
use App\Models\LOGTRANS\ConPagosRecaudos;
use App\Services\ApiAsopagos;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PagoService
{
  // Estados de transacciones
  private const ESTADO_PAGADO = 'P';
  private const ESTADO_ANULADO = 'A';


  // Configuraciones de comprobantes
  public function pagar(string $uuid, string $telefono, ApiAsopagos $apiAsopagos): array
  {
    $datosCifrados = Cache::get("pago:{$uuid}");
    if (!$datosCifrados) {
      return [
        'error' => true,
        'type'  => 'expired',
        'message' => 'Sesión expirada, consulte nuevamente.'
      ];
    }

    // Descifrar datos
    $data        = Crypt::decrypt($datosCifrados);
    $clienteData = $data['clienteData'];
    $respuesta   = $data['respuesta'];
    $cajaActiva  = $data['cajaActiva'];

    try {
      // 1. Validar saldo
      $saldo = $respuesta['additionalData']['saldo'] ?? 0;
      if ($saldo <= 0) {
        throw new Exception('Saldo insuficiente para procesar el pago');
      }

      // 2. Crear cargue y detalle
      $cargueService = new CargueService(new UsuarioService());
      $idCargue = $cargueService->obtenerOCrear($cajaActiva);

      $detalleService = new DetallePagoService(new UsuarioService());
      $idPagoDetalle = $detalleService->crearCargueDetalle($idCargue, $clienteData, $respuesta, $cajaActiva);

      $detallePago = ConDetCarguePagRec::findOrFail($idPagoDetalle);

      // 3. Ejecutar pago en la API
      $pagoResponse = $this->procesarPago($apiAsopagos, $clienteData, $respuesta, $idPagoDetalle);

      if (!$this->esPagoExitoso($pagoResponse)) {
        $this->manejarPagoFallido($pagoResponse, $detallePago);

        if (isset($pagoResponse['reverso']['responseCode']) && $pagoResponse['reverso']['responseCode'] == false) {
          return [
            'error' => true,
            'type'  => 'rollback-fail',
            'message' => 'Ocurrió un error en el pago.',
            'extra' => $pagoResponse['reverso']['authorizationRspCode'] ?? null,
          ];
        }

        return [
          'error' => true,
          'type'  => 'rollback-ok',
          'message' => 'El pago fue rechazado por el proveedor.'
        ];
      }

      // 4. Crear comprobantes y caja turno documento
      try {
        $resultado = DB::connection('oracle')->transaction(function () use (
          $idCargue,
          $pagoResponse,
          $detallePago,
          $clienteData,
          $respuesta,
          $cajaActiva,
          $idPagoDetalle,
          $telefono
        ) {

          //Actualizar detalle cargue a pagado
          $codigo = $pagoResponse['authorizationRspCode'];
          $detallePago->update([
            'estado'      => PagoService::ESTADO_PAGADO,
            'nro_interno' => $codigo
          ]);

          $saldo = $respuesta['additionalData']['saldo'] ?? 0;
          $comprobanteService = new ComprobanteService();
          $comprobante = $comprobanteService->obtenerOCrear($saldo, $cajaActiva);

          //Obtener la secuencia del bloque y grupo
          $grupoBloque = $comprobanteService->obtenerGrupoBloque($comprobante->id);
          $bloque = $grupoBloque->bloque;
          $grupo = $grupoBloque->grupo;

          //Detalle comprobante
          $comprobanteService->crearDetalleComprobante(
            $comprobante->id,
            $idPagoDetalle,
            $saldo,
            $clienteData,
            $cajaActiva,
            $telefono
          );

          $comprobanteService->crearAuxComprobante($comprobante->id, 'D', $saldo, $clienteData, $cajaActiva, $bloque, $grupo);
          $comprobanteService->crearAuxComprobante($comprobante->id, 'C', $saldo, $clienteData, $cajaActiva, $bloque, $grupo);

          $cajaTurno = new CajaTurnoDocService(new UsuarioService());
          $cajaTurno->crear($comprobante->id, $saldo, $cajaActiva);

          //Actualziar cargue a pagado
          $cargue = ConPagosRecaudos::findOrFail($idCargue);
          $cargue->update([
            'estado'  => PagoService::ESTADO_PAGADO,
          ]);

          return [
            'error'         => false,
            'cajaActiva'    => $cajaActiva,
            'comprobante'   => $comprobante,
            'idPagoDetalle' => $detallePago->id,
          ];
        });

        return $resultado;
      } catch (Exception $e) {
        //Reversar pago en la API
        Log::error('Error en PagoService, se ejecutara un reverso: ' . $e->getMessage());
        $codigo = $pagoResponse['authorizationRspCode'];

        //Respuesta de prueba
        if (config('apiAsopagos.test_mode')) {
          return [
            'error' => true,
            'type'  => false ? 'rollback-ok' : 'rollback-fail',
            'message' => false
              ? 'No fue posible completar el pago en este momento. Intente más tarde.'
              : 'Ocurrió un error en el pago.',
            'extra' => 645321
          ];
        }

        $resp = $this->manejoFalloTransaccion($codigo, $idPagoDetalle, $apiAsopagos, $clienteData, $respuesta, $e);
        return [
          'error' => true,
          'type'  => $resp['responseCode'] ? 'rollback-ok' : 'rollback-fail',
          'message' => $resp['responseCode']
            ? 'No fue posible completar el pago en este momento. Intente más tarde.'
            : 'Ocurrió un error en el pago.',
          'extra' => $codigo
        ];
      }
    } catch (Exception $e) {
      Log::error('Error en PagoService: ' . $e->getMessage());
      return [
        'error' => true,
        'type'  => 'internal',
        'message' => 'Error interno al realizar el pago.'
      ];
    } finally {
      Cache::forget("pago:{$uuid}");
    }
  }


  /**
   * Manejor transaccion fallida por causa interna
   */
  private function manejoFalloTransaccion($codigo, $idPagoDetalle, $apiAsopagos, $clienteData, $respuesta, $e)
  {

    $detallePago = ConDetCarguePagRec::findOrFail($idPagoDetalle);
    $detallePago->update([
      'estado'      => self::ESTADO_ANULADO,
      'nro_interno' => $codigo
    ]);

    //Lanzar reverso en la API
    $resp = $apiAsopagos->reversoRetiroFalloLocal(
      $clienteData['tipoIdentificacion'],
      $clienteData['identificacion'],
      $respuesta['additionalData']['saldo'],
      $clienteData['departamento'],
      $clienteData['municipio'],
      $idPagoDetalle,
      $idPagoDetalle
    );

    //Registrar reverso
    $reverso = new ReversoService();
    $reverso::crear($resp['reverso'], $codigo);

    Log::error('❌ Fallo en el proceso de pago. Se ejecutó reverso automático en la API.', [
      'cliente_identificacion' => $clienteData['identificacion'] ?? 'No disponible',
      'respuesta_reverso'      => $resp ?? 'Sin respuesta',
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
      $reverso = new ReversoService();

      // Procesar reverso si aplica
      if ($reverso::requiere($pagoResponse)) {
        $reverso::crear($pagoResponse['reverso'], $pagoResponse['authorizationRspCode'] ?? $pagoResponse['reverso']['authorizationRspCode']);
      }

      // Cambiar estado a anulado
      $detallePago->update([
        'estado'      => self::ESTADO_ANULADO,
        'nro_interno' => $pagoResponse['reverso']['authorizationRspCode'] ?? null
      ]);
    } catch (Exception $e) {
      Log::error('Error al manejar pago fallido: ' . $e->getMessage());
    }
  }


  /**
   * Procesar pago con la API
   */
  private function procesarPago(ApiAsopagos $apiAsopagos, array $clienteData, array $respuesta, int $idPagoDetalle): array
  {
    try {
      if (config('apiAsopagos.test_mode')) {
        // success
        /*  return [
          'transactionId'        => random_int(1000000000000000, 9999999999999999),
          'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
          'responseCode'         => true,
          'authorizationRspCode' => 654321,
          'errorID'              => 'E1',
        ]; */

        // Caso de prueba con error y reverso (satisfactorio/fallido)
        return [
          'error' => 'Error al procesar el pago',
          'responseCode' => true,
          'status' => 'fallo_timeout_sin_reverso',
          'reverso' => [
            'transactionId' => $idPagoDetalle,
            'transmissionDataTime' => now(),
            'responseCode' => false,
            'authorizationRspCode' => 444444,
            'errorID' => '99'
          ]
        ];
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
      Log::error('Error en API retirar: ' . $e->getMessage());
      return ['error' => 'Error de comunicación con la API', 'responseCode' => false];
    }
  }
}
