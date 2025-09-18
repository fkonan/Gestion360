<?php

namespace App\Services\Cajasan;

use App\Models\LOGTRANS\GenMunicipios;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\ApiAsopagos;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PagoConsultaService
{
  /**
   * Consultar saldo disponible y preparar datos de cliente
   */
  public function consultarSaldo(string $identificacion, int $cajaActivaId, ApiAsopagos $apiAsopagos): array
  {
    try {
      // 1. Validar cliente
      if (config('apiAsopagos.test_mode')) {
        $cliente = PerPersonas::where('identificacion', $identificacion)->first();
        if (!$cliente) {
          return ['error' => true, 'message' => 'El documento no es válido.'];
        }
      }

      // 2. Obtener caja activa
      $cajaActiva = DB::connection('oracle')
        ->table('TES_CAJATURNOS as T')
        ->join('tes_cajas as CJ', 'T.CJ_ID', '=', 'CJ.ID')
        ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
        ->select('P.NOMSUCURSAL', 'P.id as idsucursal', 'T.*')
        ->where('T.ID', $cajaActivaId)
        ->first();

      if (!$cajaActiva) {
        return ['error' => true, 'message' => 'No se encontró una caja activa válida.'];
      }

      // 3. Preparar datos cliente
      $sucursal = PerPersonas::findOrFail($cajaActiva->idsucursal);
      $municipio = GenMunicipios::findOrFail($sucursal->mu_id);


      // 4. Tipo de indentificacion
      switch ($cliente->tipdocumento) {
        case 1:
            $sigla = 'CC'; // Cédula de Ciudadanía
            break;
        case 2:
            $sigla = 'TI'; // Tarjeta de Identidad
            break;
        case 3:
            $sigla = 'NT'; // NIT
            break;
        case 5:
            $sigla = 'CE'; // Cédula de Extranjería
            break;
        case 6:
            $sigla = 'PA'; // Pasaporte
            break;
        case 43:
            $sigla = 'RC'; // Registro
            break;
        case 50:
            $sigla = 'PE'; // Permiso especial permanencia
            break;
        default:
            $sigla = 'ND'; // No Definido
            break;
      }

      if (config('apiAsopagos.test_mode')) {
        $clienteData = [
          'tipoIdentificacion'  => $sigla,
          'identificacion'      => $cliente->identificacion,
          'nombre'              => $cliente->nombreCompleto(),
          'departamento'        => $municipio->do_codigo,
          'municipio'           => $municipio->codigo
        ];
      } else {
        $clienteData = [
          'tipoIdentificacion' => 'CC',
          'identificacion' => $identificacion,
          'nombre'         => 'Usuario Prueba Cajasan',
          'departamento'   => 11,
          'municipio'      => 11001
        ];
      }


      // 4. Consultar API
      $respuesta = $this->consultarSaldoApi($apiAsopagos, $clienteData);
      if ($this->tieneErrorRespuesta($respuesta)) {
        return [
          'error' => true,
          'message' => $respuesta['additionalData']['errorMesssage'] ?? 'Error en la consulta.'
        ];
      }

      // 5. Validar saldo
      $saldo = $respuesta['additionalData']['saldo'] ?? 0;
      if ($saldo <= 0) {
        return [
          'error'   => true,
          'message' => 'Fondos insuficientes: el cliente no tiene saldo disponible para completar la operación.'
        ];
      }

      // 6. Guardar en cache
      $uuid = Str::uuid()->toString();
      $datosCifrados = Crypt::encrypt(compact('clienteData', 'respuesta', 'cajaActiva'));
      Cache::put("pago:{$uuid}", $datosCifrados, now()->addMinutes(10));

      return [
        'error' => false,
        'uuid'  => $uuid,
        'clienteData' => $clienteData,
        'respuesta'   => $respuesta,
      ];
    } catch (Exception $e) {
      Log::error('Error en PagoConsultaService: ' . $e->getMessage());
      return ['error' => true, 'message' => 'Error en la consulta, inténtelo nuevamente más tarde'];
    }
  }

  private function consultarSaldoApi(ApiAsopagos $apiAsopagos, array $clienteData): array
  {
    try {
      if (config('apiAsopagos.test_mode')) {
        return [
          'responseCode' => true,
          'additionalData' => ['saldo' => 50000],
        ];
      }

      return $apiAsopagos->consultarSaldo(
        $clienteData['tipoIdentificacion'],
        $clienteData['identificacion'],
        $clienteData['departamento'],
        $clienteData['municipio']
      );
    } catch (Exception $e) {
      Log::error('Error en API consultar saldo: ' . $e->getMessage());
      return ['error' => true, 'message' => 'Error de comunicación con la API'];
    }
  }

  private function tieneErrorRespuesta(array $respuesta): bool
  {
    return isset($respuesta['error']) ||
      ($respuesta['responseCode'] ?? false) === false ||
      !isset($respuesta['additionalData']['saldo']);
  }
}
