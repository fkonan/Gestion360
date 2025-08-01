<?php

namespace App\Services;

use App\Models\FICS\Tripulantes;
use App\Models\GESTIONPASAJES\ParametrosPasajes;
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
       
        $bloqueo = $this->levantarBloqueoFICS($conductor->identificacion, 11);

        if(!$bloqueo){    
            return toastModal("El conductor no tiene bloqueo para realizar el REGRESO ANTICIPADO, debe realizar REGRESO DE DESCANSO", "warning");
        }

        return toastModal("Se registró el evento REGRESO ANTICIPADO", "success",route("gestion-incapacidad.index"));
    } 


    public function levantarBloqueoFICS($docConductor,$idBloqueo): bool{

        //Verifica si el conductor tiene un bloqueo activo
        $tieneBloqueo = $this->tieneBloqueoFICS($docConductor, $idBloqueo);
        if(!$tieneBloqueo){
            return false;
        }

        //Levanta el bloqueo
        $tripulante = Tripulantes::with('bloqueos')
            ->where('Documento', $docConductor)
            ->where('Estado',0)
            ->first();

        $tripulanteBloqueo = $tripulante->bloqueos()
            ->where('PersonalEstadoTipoID', $idBloqueo)
            ->where('FechaFinalizacion', '>', Carbon::now()->format('Y-d-m H:i:s'))
            ->first();

        $tripulanteBloqueo->FechaFinalizacion = Carbon::now()->subDay()->format('Y-d-m H:i:s');
        $tripulanteBloqueo->save();
        return true;
    }

    public function tieneBloqueoFICS($docConductor,$idBloqueo){

        $estBloqueado = Tripulantes::where('Documento', $docConductor)
            ->where('Estado',0)
            ->whereHas('bloqueos', function ($query) use ($idBloqueo) {
                $query->where('PersonalEstadoTipoID', $idBloqueo)
                    ->where('FechaFinalizacion', '>', Carbon::now()->format('Y-d-m H:i:s'));
            })->exists();

        return $estBloqueado;
    }
  
}
