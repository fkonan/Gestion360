<?php

namespace App\Http\Middleware;

use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CajaActivaMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    $startedAt = microtime(true);
    $user = Auth::user();
    $nombreUsuario = $user?->persona?->nombreCompleto() ?? 'Usuario Desconocido';
    //$identificacion = $user?->persona?->PerNumDoc;

    //Identificacion fija para pruebas, se debe reemplazar por la del usuario autenticado
    $identificacion = 1143370629;

    if (! $identificacion) {
      session()->flash('alert', [
        'type' => 'warning',
        'title' => 'No fue posible identificar el usuario autenticado.'
      ]);

      return redirect()->back();
    }

    // Validacion de caja activa
    $consultaCajaStartedAt = microtime(true);
    $datosCaja = DB::connection('oracle')
      ->table('TES_CAJATURNOS as T')
      ->join('tes_cajas as CJ', 'T.CJ_ID', '=', 'CJ.ID')
      ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
      ->select('P.NOMSUCURSAL', 'P.id as idsucursal', 'T.*')
      ->where('T.pe_ID', function ($query) use ($identificacion) {
        $query->select('ID')
          ->from('PER_PERSONAS')
          ->where('IDENTIFICACION', $identificacion)
          ->where('ESTADO', 'ACTIVO')
          ->where('estborrado',0)
          ->limit(1);
      })
      ->where('T.ESTADO', 'T')
      ->where('T.estborrado', 0)
      ->get();
    $consultaCajaMs = (int) round((microtime(true) - $consultaCajaStartedAt) * 1000);

    if ($request->routeIs('pagosConvenios.*')) {
      PagosRecaudosLogger::debug('Tiempo de resolucion de caja activa', [
        'operation' => 'caja_activa',
        'identificacion_consultada' => $identificacion,
        'duracion_ms' => $consultaCajaMs,
        'cantidad_cajas' => $datosCaja->count(),
      ]);
    }

    if ($datosCaja->isEmpty()) {
      session()->flash('alert', [
        'type' => 'warning',
        'title' => 'La caja de ' . $nombreUsuario . ' no está activa.'
      ]);
      return redirect()->back();
    }

    $request->attributes->set('caja_activa', $datosCaja);

    if ($request->routeIs('pagosConvenios.*')) {
      PagosRecaudosLogger::debug('Tiempo total del middleware de caja activa', [
        'operation' => 'caja_activa',
        'duracion_ms' => (int) round((microtime(true) - $startedAt) * 1000),
      ]);
    }

    return $next($request);
  }
}
