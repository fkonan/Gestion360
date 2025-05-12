<?php

namespace App\Http\Middleware;

use App\Models\GESTIONADMIN\Modulo;
use App\Models\GESTIONADMIN\SubModulo;
use Closure;
use Illuminate\Http\Request;

class SubModuloActivoMiddleware
{
    //Verfica que el submodulo no este dehabilitado en la base de datos
    //Si el submodulo esta deshabilitado, redirige al home con un mensaje de advertencia
    public function handle(Request $request, Closure $next, $moduleId)
    {
        $subModule = SubModulo::where('IdSubModulo', $moduleId)->first();

        if (!$subModule || $subModule->ModEstado !== 'ACTIVO' ) {
            session()->flash('alert', ['type' => 'warning','title' => 'El módulo "' . ($subModule->ModNom ?? 'desconocido') . '" se encuentra deshabilitado.']);
            return redirect()->back();
        }

        return $next($request);
    }
}
