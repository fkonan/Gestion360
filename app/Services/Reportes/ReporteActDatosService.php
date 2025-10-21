<?php

namespace App\Services\Reportes;

use App\Models\GESTIONPASAJES\ActualizacionDatos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Exception;

class ReporteActDatosService
{
  /**
   * Obtiene la lista de actualizaciones de datos según el filtro seleccionado.
   */
  public function obtenerData(string $filtro): Collection
  {
    try {
      $query = ActualizacionDatos::query();

      // Aplicar filtros
      if ($filtro === 'carga') {
        $query->whereIn('CargoPer', ['CONDUCTOR CARGA']);
      } elseif ($filtro === 'pasajes') {
        $query->whereIn('CargoPer', [
          'CONDUCTOR PASAJES',
          'CONDUCTOR CACIQUE DE ORO',
          'CONDUCTOR TURNADOR CACIQUE DE ORO'
        ]);
      }

      // JOIN con última fecha de firma
      $actDatos = $query
        ->join(DB::connection('mysql-gestion-pasajes')->raw('(
          SELECT DocNumPer, MAX(CONCAT(FirFecReg, " ", FirHorReg)) AS ultima_fecha
          FROM otro_si
          GROUP BY DocNumPer
        ) AS ultimos'), function ($join) {
          $join->on('otro_si.DocNumPer', '=', 'ultimos.DocNumPer')
            ->whereRaw('CONCAT(otro_si.FirFecReg, " ", otro_si.FirHorReg) = ultimos.ultima_fecha');
        })
        ->select([
          'otro_si.id as id',
          'otro_si.NomPer as nombre',
          'otro_si.DocNumPer as documento',
          'otro_si.ConTraPer as contrato',
          'otro_si.CargoPer as cargo',
          'otro_si.MailPer as correo',
          'otro_si.NumTelPer as telefono',
          'otro_si.DirPer as direccion',
          'otro_si.MunFir as municipio_firma',
          'otro_si.DepFir as departamento_firma',
          'otro_si.FirFecReg as fecha_firma',
          'otro_si.FirHorReg as hora_firma',
          'otro_si.FirmaIp as ip_firma'
        ])
        ->orderBy('otro_si.FirFecReg', 'desc')
        ->orderBy('otro_si.FirHorReg', 'desc')
        ->get();

      return $actDatos;
    } catch (Exception $e) {
      throw new Exception("Error al obtener los datos de actualización: " . $e->getMessage());
    }
  }
}
