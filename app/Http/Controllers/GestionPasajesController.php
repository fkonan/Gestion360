<?php

namespace App\Http\Controllers;

use App\Models\GESTIONPASAJES\MunicipioPasajes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GestionPasajesController extends Controller
{
    public function formBuscarViaje(){
        $municipios = MunicipioPasajes::with('departamento')
            ->orderBy('MunNom', 'asc')
            ->get();

        return view('gestionpasajes.formBuscarViaje',compact('municipios'));
    }

    public function filtrarViajes(Request $request){

        $validator = Validator::make($request->all(), [
            'origen' => 'required',
            'destino' => 'required|different:origen',
        ], [
            'destino.different' => 'El destino debe ser diferente al origen.',
        ]);

        if ($validator->fails()) {
            return toast('EL origen debe ser diferente al destino', 'error', redirect()->route('roles.index'));
        }

        $origen = MunicipioPasajes::find($request->origen);
        $destino = MunicipioPasajes::find($request->destino);
       

        return view('gestionpasajes.resultadoViaje', compact('origen', 'destino', 'fecha'));
    }
}
