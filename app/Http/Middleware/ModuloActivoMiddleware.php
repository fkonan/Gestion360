<?php

namespace App\Http\Middleware;

use App\Models\Modulo;
use Closure;
use Illuminate\Http\Request;

class ModuloActivoMiddleware
{
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
