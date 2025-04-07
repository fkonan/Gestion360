<?php

namespace App\Http\Middleware;

use App\Models\Modulo;
use Closure;
use Illuminate\Http\Request;

class ModuloActivoMiddleware
{
    //Verfica que el modulo no este dehabilitado en la base de datos
    //Si el modulo esta deshabilitado, redirige a la pagina anterior con un mensaje de advertencia
    public function handle(Request $request, Closure $next, $moduleId)
    {
        $module = Modulo::where('IdModulo', $moduleId)->first();

        if (!$module || $module->ModEstado !== 'ACTIVO' ) {
            session()->flash('alert', ['type' => 'warning','title' => 'El módulo "' . $module->ModNom . '" está deshabilitado.']);
            return back();
        }

        return $next($request);
    }
}
