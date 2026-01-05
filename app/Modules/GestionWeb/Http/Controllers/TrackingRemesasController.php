<?php

namespace App\Modules\GestionWeb\Http\Controllers;

use App\Http\Controllers\Controller;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingRemesasController extends Controller
{
  public function consultar(Request $request)
  {
    try {
      $numeroRemision = $request->numeroRemision;

      $query = "
                SELECT
                    r.tipentrega,
                    go.nombre as nombreorigen,
                    r.fecelabora,
                    gd.nombre as nombredestino,
                    r.feccumple,
                    NVL(r.NUMREMESA, r.numremesafac) AS REMESA,
                    obs.descripcion,
                    obs.feccreacion,
                    tn.descripcion,
                    CASE
                        WHEN po.razonsocial IS NOT NULL THEN po.razonsocial
                        ELSE po.pnombre || ' ' || po.papellido
                    END AS REMITENTE,
                    CASE
                        WHEN pd.razonsocial IS NOT NULL THEN pd.razonsocial
                        ELSE pd.pnombre || ' ' || pd.papellido
                    END AS DESTINATARIO,
                    td.descripcion AS ESTADO,
                    td.id AS ID_ESTADO_REMESA,
                    r.id
                FROM for_remesas r
                INNER JOIN gen_municipios go ON r.mu_id_origen = go.id
                INNER JOIN gen_municipios gd ON r.mu_id_destino = gd.id
                INNER JOIN per_personas po ON r.pe_id_remite = po.id
                INNER JOIN per_personas pd ON r.pe_id_destino = pd.id
                INNER JOIN gen_tipoestadodctos td ON r.te_id = td.id
                LEFT JOIN cum_notacumplidos obs ON obs.re_id = r.id
                LEFT JOIN cum_tiponotacumplidos tn ON obs.tnv_id = tn.id
                WHERE (r.numremesa = :numero OR r.numremesafac = :numero) AND r.tipremesa IN (1, 7)
            ";

      $datos = DB::connection('oracle')->select($query, ['numero' => $numeroRemision]);

      if (empty($datos)) {
        return toast("No se encontraron resultados para el número de remesa, verifique e intente nuevamente", "info", route('trackingRemesas.index'));
      } else if ($datos[0]->id_estado_remesa == 5) {
        return toast("La remesa consultada esta anulada", "warning", route('trackingRemesas.index'));
      }

      $pasos = [
        [
          'icon' => 'fas fa-warehouse',
          'label' => 'Bodega origen',
          'img' => 'https://autogestion.copetran.com.co/cdn/img/iconos/checkCarga_',
          'activo' => in_array($datos[0]->id_estado_remesa, [1, 6, 18, 10, 17, 25, 4, 23])
        ],
        [
          'icon' => 'fas fa-truck-moving',
          'label' => 'Viajando',
          'img' => 'https://autogestion.copetran.com.co/cdn/img/iconos/checkCarga_',
          'activo' => in_array($datos[0]->id_estado_remesa, [6, 18, 10, 17, 25, 4, 23])
        ],
        [
          'icon' => 'fas fa-warehouse',
          'label' => 'Bodega Destino',
          'img' => 'https://autogestion.copetran.com.co/cdn/img/iconos/checkCarga_',
          'activo' => in_array($datos[0]->id_estado_remesa, [10, 17, 25, 4, 23])
        ],
        [
          'icon' => 'fas fa-flag-checkered',
          'label' => 'Entregado',
          'img' => 'https://autogestion.copetran.com.co/cdn/img/iconos/checkCarga_',
          'activo' => in_array($datos[0]->id_estado_remesa, [25, 4, 23])
        ],
      ];

      return view('remesas.resultado', compact('datos', 'pasos'));
    } catch (Exception $e) {
      Log::error('Error al consultar remesas: ' . $e->getMessage());
      return toast("Error al consultar remesas", "error", route('trackingRemesas.index'));
    }
  }
}
