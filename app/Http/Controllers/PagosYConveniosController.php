<?php

namespace App\Http\Controllers;

use App\Services\ApiAsopagos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagosYConveniosController extends Controller
{
    public function index(){
        return view('pagosRecaudos.index');
    }

    public function consultar(Request $request, ApiAsopagos $apiAsopagos){
        try{
             //respuesta de prueba
            $respuesta = [
                'responseCode' => true,
                'additionalData' => [
                    'saldo' => 20000000
                ],
            ];

            //Consulta a la API
            /* $respuesta = $apiAsopagos->consultarSaldo('CC',$request->identificacion,11,11001);  */

            if(isset($respuesta['error']) || $respuesta['responseCode'] == false){
                return toastModal('Error en la consulta, intentelo nuevamente más tarde', 'danger');
            }

            //Informacion adicional del usuario (prueba)
            $userData = [
                'identificacion' => $request->identificacion,
                'nombre' => 'Sergio Andrés Carrillo'
            ];

            //Data guardad en sesion para usarse en todo el proceso
            session([
                'userData_temp' => $userData,
                'respuesta_temp' => $respuesta
            ]);

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

    public function validarModal(){
        //validar que los datos en sesion existan
        if (!session()->has('userData_temp') || !session()->has('respuesta_temp')) {
            return response()->view('home'); 
        }

        $userData = session('userData_temp');
        $respuesta = session('respuesta_temp');

        return view("pagosRecaudos.validarPago",compact('userData','respuesta'));
    }
}
