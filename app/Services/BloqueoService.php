<?php

namespace App\Services;

use App\Models\FICS\Tripulantes;
use App\Models\LOGTRANS\PerPersonaBloqueo;
use App\Models\LOGTRANS\PerPersonas;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class BloqueoService
{   
    // Bloqueos de Logtrans
    public const ID_BLOQUEO_LOGTRANS_INCAPACIDAD = 45;
    public const ID_BLOQUEO_LOGTRANS_SARLAFT = 77;

    // Bloqueos de FICS
    public const ID_BLOQUEO_FICS_PREOPERACIONAL = 33;

    //Cargos que reciben bloqueos
    public const CARGOS_BLOQUEO = [
        206, // CONDUCTOR CARGA
        207, // CONDUCTOR PASAJES
        1138045160, // CONDUCTOR TURNADOR CACIQUE DE ORO
        1138045159, // CONDUCTOR CACIQUE DE ORO
    ];
    
    public function bloqNovedadLogtransInc($IdPerOracle,$incapacidadDatos)
    {
        try{
            $persona = PerPersonas::where('id', $IdPerOracle)
                ->where('estado', 'ACTIVO')
                ->where('estborrado', 0)
                ->first();

            //Verifica si la persona existe y si es un conductor
            if (!$persona || !in_array($persona->PerContratoPersona->cargo, self::CARGOS_BLOQUEO)) {
                return false;
            }

            //Información para generar el bloqueo
            $ultimoId = PerPersonaBloqueo::max('id') + 1;
            $fecha = Carbon::now()->format('Y-m-d H:i:s');
            $fechaInicial = Carbon::parse($incapacidadDatos->IncFecIni);
            $fechaFinal = Carbon::parse($incapacidadDatos->IncFecFin);

            $bloqueo = new PerPersonaBloqueo();
            $bloqueo->id = $ultimoId;
            $bloqueo->cedula_conductor = $persona->identificacion;
            $bloqueo->tb_id = self::ID_BLOQUEO_LOGTRANS_INCAPACIDAD;
            $bloqueo->descripcion = 'INC '. $fechaInicial->format('d/m/Y H:i:s') .' AL '.$fechaFinal->format('d/m/Y H:i:s').' ('. $incapacidadDatos->diagnostico->CodigoCie .') APROBADO POR RRHH POR gestion.copetran.com.co';
            $bloqueo->pe_id_bloqueo = 1329752424;
            $bloqueo->fecbloqueo = $fecha;
            $bloqueo->activo = 1;
            $bloqueo->pe_id_desbloqueo = null;
            $bloqueo->fecdesbloqueo = null;
            $bloqueo->estborrado = 0;
            $bloqueo->fecmodifica = $fecha;
            $bloqueo->empmodifica = 6761;
            $bloqueo->usrmodifica = 1329752424;
            $bloqueo->rolmodifica = 60;
            $bloqueo->feccreacion = $fecha;
            $bloqueo->empcreacion = 6761;
            $bloqueo->usrcreacion = 1329752424;
            $bloqueo->fec_inicio = $fechaInicial->format('Y/m/d H:i:s');
            $bloqueo->fec_fin = $fechaFinal->format('Y/m/d H:i:s');
            $bloqueo->save();
            return "bloqueado";
        }catch(Exception $e){
            Log::error('Error al generar la novedad en Logtrans: ' . $e->getMessage());
            return "error";
        }
    }

    public static function tieneBloqueoLogtrans($identificacion, $idBloqueo)
    {
        return PerPersonaBloqueo::where('cedula_conductor', $identificacion)
            ->where('tb_id', $idBloqueo)
            ->where('estborrado', 0)
            ->where('activo', 1)
            ->where(function ($q) {
                $q->whereNull('fec_fin')            
                ->orWhere('fec_fin', '>=', now()); 
            })
            ->exists();
    }

    public static function crearNovedadEmpleado($identificacion, $descripcion, $idBloqueo)
    {
        try{
            $estBloqueado = BloqueoService::tieneBloqueoLogtrans($identificacion,$idBloqueo);
            
            //Si ya tiene el bloqueo no se genera otro
            if($estBloqueado){
                return true;
            }

            //Información para generar el bloqueo
            $ultimoId = PerPersonaBloqueo::max('id') + 1;
            $fecha = Carbon::now()->format('Y-m-d H:i:s');
            $usuarioId = EmpleadoService::idPersonaLogtrans($identificacion);
        
            $bloqueo = new PerPersonaBloqueo();
            $bloqueo->id = $ultimoId;
            $bloqueo->cedula_conductor = $identificacion;
            $bloqueo->tb_id = $idBloqueo;
            $bloqueo->descripcion = $descripcion;
            $bloqueo->pe_id_bloqueo = $usuarioId;
            $bloqueo->fecbloqueo = $fecha;
            $bloqueo->activo = 1;
            $bloqueo->pe_id_desbloqueo = null;
            $bloqueo->fecdesbloqueo = null;
            $bloqueo->estborrado = 0;
            $bloqueo->fecmodifica = $fecha;
            $bloqueo->empmodifica = 6761;
            $bloqueo->usrmodifica = $usuarioId;
            $bloqueo->rolmodifica = 60;
            $bloqueo->feccreacion = $fecha;
            $bloqueo->empcreacion = 6761;
            $bloqueo->usrcreacion = $usuarioId;
            $bloqueo->fec_inicio = $fecha;
            $bloqueo->fec_fin = null;
            $bloqueo->save();

            return true;
        }catch(Exception $e){
            Log::error('Error al generar la novedad en Logtrans: ' . $e->getMessage());
            return false;
        }
        
    }

    public static function tieneBloqueoFICS($docConductor,$idBloqueo){

        $estBloqueado = Tripulantes::where('Documento', $docConductor)
            ->where('Estado',0)
            ->whereHas('bloqueos', function ($query) use ($idBloqueo) {
                $query->where('PersonalEstadoTipoID', $idBloqueo)
                    ->where('FechaFinalizacion', '>', Carbon::now()->format('Y-d-m H:i:s'));
            })->exists();

        return $estBloqueado;
    }


    public static function levantarBloqueoFICS($docConductor,$idBloqueo): bool{

        //Verifica si el conductor tiene un bloqueo activo
        $tieneBloqueo = self::tieneBloqueoFICS($docConductor, $idBloqueo);
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
  
}
