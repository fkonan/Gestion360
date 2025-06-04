<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\FICS\Tripulantes;
use App\Models\GESTIONPASAJES\FirmaEquipajePol;
use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\LOGTRANS\PerConductoresEventos;
use App\Models\LOGTRANS\PerContratoPersona;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\EventoConductorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConductorController extends Controller
{
    public function formActualizarEstado(){
        return view("conductores.actualizarEstado");
    }

    //FICS 
    public function actualizarEstadoConductor(Request $request){
        $validator = Validator::make($request->all(), [
            'legajo' => ['required'],
        ], [
            'legajo.required' => 'El campo legajo es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $tripulante = Tripulantes::where("legajo", $request->legajo)->first();
           
            if (!$tripulante) {
                return toastModal("El legajo no corresponde a ningun conductor", "warning");
            }

            //Cambia el estado dependiendo del que tenga actualmente 
            $tripulante->Estado = $tripulante->Estado == 1 ? 0 : 1;
            $tripulante->save();

            $estado = $tripulante->Estado === 1 ? 'ACTIVADO' : 'SUSPENDIDO';

            return toastModal("Se cambió el estado del conductor a $estado en el sistema", "success");  

        }catch(Exception $e){
            Log::error('Error al actualizar estado de conductor FICS: ' . $e->getMessage());
            return toastModal("Error al actualizar estado de conductor","error");
        }    
    }

    public function reporteFirmaEquipaje(){
        return view("reportes.firmaConductores");
    }


    public function filtrarFirmaEquipaje(Request $request){
        $validator = Validator::make($request->all(), [
            'codigo' => ['nullable', 'regex:/^\d{4}$/'],
            'identificacion' => ['nullable', 'numeric'],
        ], [
            'codigo.regex' => 'El legajo debe ser un número de 4 dígitos.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            if($request->codigo != null && $request->identificacion != null){
                $listaFirmas = FirmaEquipajePol::where("CodCon",$request->codigo)
                    ->where("DocCon",$request->identificacion)
                    ->get();
            }elseif($request->codigo != null){
                $listaFirmas = FirmaEquipajePol::where("CodCon",$request->codigo)
                    ->get();
            }elseif($request->identificacion != null){
                $listaFirmas = FirmaEquipajePol::where("DocCon",$request->identificacion)
                    ->get();
            }else{
                $listaFirmas = FirmaEquipajePol::all();
            }

            if ($listaFirmas->isEmpty()) {
                return toastModal("No se encontraron resultados para los parametros ingresados.", "warning","#");
            }else{
                session(['firmasEquipaje' => $listaFirmas]);
                $numeroRegistros = $listaFirmas->count();
                return toastModal('Se han encontrado ' . $numeroRegistros . ' registros para los parametros seleccionadas', "success",route("lista.firmaEquipaje"));
            }
        
        }catch(Exception $e){
            Log::error('Error al obtener la lista de firmas politica equipaje: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados","error");
        }

    }

    public function listaFirmasEquipaje(){
        return view('reportes.listaFirmasEquipaje');
    }

    public function cargarDataFirmaEquipaje(){
        $firmasEquipaje = session('firmasEquipaje') ?? [] ;
        return $firmasEquipaje;
    }

    public function formDescansoConductores(){
        $parametrosDescansoConductores = ParametrosPasajes::getDescansoConductores();
        return view('conductores.descansoConductores',compact("parametrosDescansoConductores"));
    }

    public function registrarEvento(Request $request, EventoConductorService $eventoConductorService){
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
            'fecha' => ['required','date']
        ], [
            'identificacion.regex' => 'El campo identificación no tiene un formato valido',
            'identificacion.required' => 'El campo identificación es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $respuesta = $eventoConductorService->registrarEventoDescanso($request);
        return $respuesta;
    }

    public function formIngSalConductores(){
        return view("reportes.ingresoSalidaConductores");
    }

    public function listaIngSalConductores(){
        return view("reportes.reporteIngresoSalida");
    }


    public function reporteIngSalConductores(Request $request){
        $validator = Validator::make($request->all(), [
            'fechaInicial' => 'date',
            'fechaFinal' => [
                'date',
                'after_or_equal:fechaInicial',
            ],
        ], [
            'fechaFinal.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',  
            'fechaInicial.date' => 'La fecha de fin debe ser una fecha válida.',
            'fechaFinal.date' => 'La fecha de fin debe ser una fecha válida.',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $fechaIni = Carbon::parse($request->fechaInicial)->format('Y/m/d');
            $fechaFin = Carbon::parse($request->fechaFinal)->format('Y/m/d');

            $query = DB::connection('oracle')
                ->table('LOGTRANSPRO.per_conductoreseventos as ce')
                ->leftJoin('logtranspro.per_personas as p', 'p.id', '=', 'ce.pe_id')
                ->join('logtranspro.per_contrato_persona as pcp', 'pcp.pe_id_pe', '=', 'ce.pe_id')
                ->select(
                    'ce.id',
                    'p.identificacion',
                    'pcp.codigo',
                    DB::raw("p.PNOMBRE || NVL(' ' || p.SNOMBRE, '') || ' ' || p.PAPELLIDO || NVL(' ' || p.SAPELLIDO, '') as conductor"),
                    'ce.anotacion',
                    'ce.fechaevento',
                    DB::raw("(SELECT s.NOMSUCURSAL FROM per_personas s WHERE s.id = ce.Empcreacion) AS agencia")
                )
                ->whereRaw("TRUNC(ce.FECHAEVENTO) >= TRUNC(TO_DATE(?, 'YYYY/MM/DD'))", [$fechaIni])
                ->whereRaw("TRUNC(ce.FECHAEVENTO) <= TRUNC(TO_DATE(?, 'YYYY/MM/DD'))", [$fechaFin])
                ->whereIn('ce.evento', ['49', '50']);

            // Añadir filtros según la variable "filtro"
            switch ($request->filtro) {
                case 'identificacion':
                    $query->where('p.identificacion', 'like', $request->parametroInput);
                    break;

                case 'codigo':
                    $query->where('pcp.codigo', 'like', $request->parametroInput);
                    break;

                case 'todos':
                    $query->where('p.identificacion', 'like', '%')
                        ->where('pcp.codigo', 'like', '%');
                    break;
            } 

            // Ordenar y ejecutar
            $resultados = $query->orderBy('ce.FECHAEVENTO', 'desc')->get();
            $numeroRegistros = $resultados->count();

            if ($numeroRegistros == 0) {
                return toastModal("No se encontraron resultados para los parametros ingresados.", "warning","#");
            }

            session(['ingSalConductores' => $resultados]);
            return toastModal("Registros encontrados: ".$numeroRegistros ,"success",route("lista.ingresoSalidas"));
        }catch(Exception $e){
            Log::error('Error al obtener la lista de ingreso salida de conductores: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados","error");
        }
    }

    public function cargarDataIngSalConductores(){
        $data = session('ingSalConductores') ?? [] ;
        return $data;
    }
}
