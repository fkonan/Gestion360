<?php

namespace App\Http\Middleware;

use App\Models\GESTIONADMIN\Modulo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CajaActivaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $nombreUsuario = $user->persona->nombreCompleto() ?? 'Usuario Desconocido';
        /* $identificacion = $user->persona->identificacion; */
        $identificacion = '1143139437'; 

        // Validacion de caja activa
        $datosCaja = DB::connection('oracle')
            ->table('TES_CAJATURNOS as T')
            ->join('tes_cajas as CJ', 'T.CJ_ID', '=', 'CJ.ID')
            ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
            ->select('P.NOMSUCURSAL','P.id as idsucursal','T.*')
            ->where('T.pe_ID', function ($query) use ($identificacion) {
                $query->select('ID')
                    ->from('PER_PERSONAS')
                    ->where('IDENTIFICACION', $identificacion)
                    ->limit(1);
            })
            ->where('T.ESTADO', 'T')
            ->where('T.estborrado', 0)
            ->get();

        if ($datosCaja->isEmpty()) {
            session()->flash('alert', [
                'type' => 'warning',
                'title' => 'La caja de ' . $nombreUsuario . ' no está activa.'
            ]);
            return redirect()->back();
        }
        
        $request->attributes->set('caja_activa', $datosCaja);
        return $next($request);
    }
}
