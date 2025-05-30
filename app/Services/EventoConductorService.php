<?php

namespace App\Services;

use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\LOGTRANS\PerBloqueoConNov;
use App\Models\LOGTRANS\PerConductoresEventos;
use App\Models\LOGTRANS\PerPersonas;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class EventoConductorService
{   
    //EVENTOS DESCANSO
    public const REGRESO_DE_DESCANSO = 49;
    public const SALIDA_A_DESCANSO = 50;
    public const REGRESO_ANTICIPADO = 25;

    public function registrarEventoDescanso($request){
        try{
            $conductor = PerPersonas::where("identificacion", $request->identificacion)
                ->where("estborrado", 0)
                ->where("estado", "ACTIVO")
                ->whereIn("tipdocumento", [1])
                ->first();

            if(!$conductor){
                return toastModal("El numero de identificacion es incorrecto o no es valido actualmente.", "error");
            };

            if(!in_array($request->evento, [self::REGRESO_ANTICIPADO, self::REGRESO_DE_DESCANSO, self::SALIDA_A_DESCANSO])){
                return toastModal("Ocurrio un error con el evento seleccionado, intento nuevamente mas tarde", "error");
            }

            //El regreso anticipado maneja un logica distinta a los otros casos
            if($request->evento == self::REGRESO_ANTICIPADO){   
                return $this->registrarRegresoAnticipado($conductor,$request);
            }

            //formatear fecha a formato correcto
            $fecha = Carbon::parse($request->fecha)->format('Y/m/d');
            $eventoDesc = ParametrosPasajes::where("ParNom", $request->evento)->value('ParDes');

            $conductorEvento = new PerConductoresEventos();
            $conductorEvento->pe_id = $conductor->id;
            $conductorEvento->fechaevento = $request->fecha;
            $conductorEvento->evento = $request->evento;
            $conductorEvento->anotacion = $eventoDesc;
            $conductorEvento->fecmodifica = $fecha;
            $conductorEvento->usrmodifica = 1149061885;
            $conductorEvento->rolmodifica = 60;
            $conductorEvento->empmodifica = 6831;
            $conductorEvento->estborrado = 0;
            $conductorEvento->feccreacion = $fecha;
            $conductorEvento->usrcreacion = 1149061885;
            $conductorEvento->empcreacion = 6831;
            $conductorEvento->tiporegistro = 0;
            $conductorEvento->save();
 
            return toastModal("Se registró el evento ". $eventoDesc, "success",route("reportes.index"));
           
        }catch(Exception $e){
            Log::error('Error al registrar el descanso: ' . $e->getMessage());
            return toastModal("Error al registrar el descanso","error");
        }
       
    }

    private function registrarRegresoAnticipado($conductor){
        $conductorBloqueo = PerBloqueoConNov::where("id_conducevento", $conductor->id)
                    ->where("estborrado", 0)
                    ->where("fecha_fin", ">", Carbon::now())
                    ->orderBy('id', 'desc')
                    ->first();

        if(!$conductorBloqueo){
            return toastModal("El conductor no presenta bloqueo para realizar el REGRESO ANTICIPADO, debe realizar REGRESO DE DESCANSO", "warning");
        }

        //Se cambia la fecha de bloqueo por 1 dia antes al actual
        $conductorBloqueo->fecha_fin = Carbon::now()->subDay();
        $conductorBloqueo->save();

        return toastModal("Se registró el evento REGRESO ANTICIPADO", "success",route("reportes.index"));
    } 
  
}
