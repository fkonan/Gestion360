<?php

namespace App\Http\Middleware;

use App\Models\GESTIONADMIN\Modulo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CajaActivaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica si el usuario tiene una caja activa
        $user = Auth::user();
        $nombreUsuario = $user->persona->nombreCompleto() ?? 'Usuario Desconocido';

        // Validacion de caja activa
        $activa = true;
 
        if (!$activa) {
            session()->flash('alert', ['type' => 'warning','title' => 'La caja de ' . $nombreUsuario . ' no está activa.']);
            return redirect()->back();
        }

        return $next($request);
    }
}
