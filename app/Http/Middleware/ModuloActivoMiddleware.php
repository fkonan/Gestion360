<?php

namespace App\Http\Middleware;

use App\Models\GESTIONADMIN\Modulo;
use Closure;
use Illuminate\Http\Request;

class ModuloActivoMiddleware
{
  //Verfica que el modulo no este dehabilitado en la base de datos
  //Si el modulo esta deshabilitado, redirige al home con un mensaje de advertencia
  public function handle(Request $request, Closure $next, $moduleId)
  {
    $module = Modulo::where('IdModulo', $moduleId)->first();

    if (!$module || $module->ModEstado !== 'ACTIVO') {
      session()->flash('alert', ['type' => 'warning', 'title' => 'El módulo "' . ($module->ModNom ?? 'desconocido') . '" se encuentra deshabilitado.']);
      return redirect()->back();
    }

    return $next($request);
  }
}
