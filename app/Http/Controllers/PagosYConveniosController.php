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
            /* $respuesta = $apiAsopagos->consultarSaldo('CC',$request->identificacion,11,11001); */

            //respuesta de prueba
            $respuesta = [
                'responseCode' => true,
                'additionalData' => [
                    'saldo' => 20000000
                ],
            ];

            if(isset($respuesta['error']) || $respuesta['responseCode'] == false){
                return toastModal('Error en la consulta, intentelo nuevamente más tarde', 'danger');
            }

            $userData = [
                'identificacion' => $request->identificacion,
                'nombre' => 'Sergio Andrés Carrillo'
            ];

            return response()->json([
                'success' => true,
                'html' => view('pagosRecaudos.pagosDisponibles', [
                    'data' => $respuesta,
                    'userData' => $userData
                ])->render()
            ]);

        }catch(Exception $e){
            Log::error('Error al consultar por la identficacion (asopagos) ' . $e->getMessage());
            return toastModal('Error en la consulta, intentelo nuevamente más tarde', 'danger');
        }
    }
}
