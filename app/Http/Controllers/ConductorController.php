<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Support\Facades\Log;

use App\Models\FICS\Tripulantes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConductorController extends Controller
{
    public function formActualizarEstado(){
        return view("conductores.actualizarEstado");
    }

    //FICS 
    public function actualizarEstadoConductor(Request $request){
        $validator = Validator::make($request->all(), [
            'legajo' => ['required', 'regex:/^\d{4}$/'],
        ], [
            'legajo.required' => 'El campo legajo es obligatorio.',
            'legajo.regex' => 'El legajo debe ser un número de 4 dígitos.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $tripulante = Tripulantes::where("legajo", $request->legajo)->first();
           
            if (!$tripulante) {
                return sweetAlertJson("El legajo no corresponde a ningun conductor", "warning");
            }

            //Cambia el estado dependiendo del que tenga actualmente 
            $tripulante->Estado = $tripulante->Estado == 1 ? 0 : 1;
            $tripulante->save();

            $estado = $tripulante->Estado === 1 ? 'ACTIVADO' : 'SUSPENDIDO';

            return sweetAlertJson("Se cambió el estado del conductor a $estado en el sistema", "success");
            

        }catch(ExceptioN $e){
            Log::error('Error al actualizar estado de conductor FICS: ' . $e->getMessage());
            return sweetAlertJson("Error al actualizar estado de conductor","error");
        }    
    }
}
