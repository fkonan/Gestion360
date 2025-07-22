<?php

namespace App\Http\Controllers;

use App\Services\ApiAsopagos;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagosYConveniosController extends Controller
{
    public function index(){
        return view('pagosRecaudos.index');
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos){
        try{
            $respuesta = $apiAsopagos->consultarSaldo('CC',$request->identificacion,11,11001);

            if(isset($respuesta['error'])){
                return toast('Error en la consulta, intentelo nuevamente más tarde', 'danger');
            }
            dd($respuesta);

        }catch(Exception $e){
            Log::error('Error al consultar la identficacion (asopagos) ' . $e->getMessage());
            return toast('Error en la consulta, intentelo nuevamente más tarde', 'danger');
        }
    }
}
