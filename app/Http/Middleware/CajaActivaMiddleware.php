<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajaActivaMiddleware
{
  public function handle(Request $request, Closure $next)
  {
    $user = Auth::user();
    $esSuperAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole(User::SUPER_ADMIN_ROLE);

    if ($esSuperAdmin) {
      $request->attributes->set('caja_activa', null);
      $request->attributes->set('caja_activa_bypass', true);

      Log::channel('pagos_recaudos')->info('Middleware caja activa omitido para super admin', [
        'module' => 'pagos_recaudos',
        'operation' => 'caja_activa',
        'route' => $request->route()?->getName(),
        'method' => $request->method(),
        'auth_user_id' => Auth::id(),
        'host' => gethostname(),
      ]);

      return $next($request);
    }

    $nombreUsuario = $user?->persona?->nombreCompleto() ?? 'Usuario Desconocido';
    $identificacion = trim((string) ($user?->persona?->PerNumDoc ?? ''));
    /* $identificacion = 1143370629; */

    if (! $identificacion) {
      Log::channel('pagos_recaudos')->warning('Middleware caja activa sin identificacion de usuario', [
        'module' => 'pagos_recaudos',
        'operation' => 'caja_activa',
        'route' => $request->route()?->getName(),
        'method' => $request->method(),
        'auth_user_id' => Auth::id(),
        'host' => gethostname(),
      ]);

      session()->flash('alert', [
        'type' => 'warning',
        'title' => 'No fue posible identificar el usuario autenticado.'
      ]);

      return redirect()->back();
    }

    // Validacion de caja activa
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

    Log::channel('pagos_recaudos')->info('Resultado middleware caja activa', [
      'module' => 'pagos_recaudos',
      'operation' => 'caja_activa',
      'route' => $request->route()?->getName(),
      'method' => $request->method(),
      'auth_user_id' => Auth::id(),
      'identificacion_consultada' => $identificacion,
      'cantidad_cajas' => $datosCaja->count(),
      'host' => gethostname(),
    ]);

    if ($datosCaja->isEmpty()) {
      Log::channel('pagos_recaudos')->warning('Middleware caja activa sin resultados', [
        'module' => 'pagos_recaudos',
        'operation' => 'caja_activa',
        'route' => $request->route()?->getName(),
        'method' => $request->method(),
        'auth_user_id' => Auth::id(),
        'identificacion_consultada' => $identificacion,
        'host' => gethostname(),
      ]);

      session()->flash('alert', [
        'type' => 'warning',
        'title' => 'La caja de ' . $nombreUsuario . ' no esta activa.'
      ]);
      return redirect()->back();
    }

    $request->attributes->set('caja_activa', $datosCaja);

    return $next($request);
  }
}
