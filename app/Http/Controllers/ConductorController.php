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
        return view("reportes.conductores.firmaPolEquipaje");
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
            $columns = [
                DB::raw('CodCon as codigo'),
                DB::raw('DocCon as identificacion'),
                DB::raw('NomCon as nombre_completo'),
                DB::raw('FirFecReg as fecha_registro'),
                DB::raw('FirHorReg as hora_registro')
            ];

            $query = FirmaEquipajePol::select($columns);

            if($request->codigo != null && $request->identificacion != null){
                $query->where("CodCon",$request->codigo)
                      ->where("DocCon",$request->identificacion);
            }elseif($request->codigo != null){
                $query->where("CodCon",$request->codigo);
            }elseif($request->identificacion != null){
                $query->where("DocCon",$request->identificacion);
            }

            $listaFirmas = $query->get();

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
        return view('reportes.conductores.listaFirmasEquipaje');
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
        return view("reportes.conductores.formIngresosSalidas");
    }

    public function listaIngSalConductores(){
        return view("reportes.conductores.ingresosSalidas");
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
                ->where('ce.EMPCREACION', '!=', 1124882226) //PROYECTO DRUMMOND - LA LOMA
                ->leftJoin('logtranspro.per_personas as p', 'p.id', '=', 'ce.pe_id')
                ->join('logtranspro.per_contrato_persona as pcp', 'pcp.pe_id_pe', '=', 'ce.pe_id')
                ->leftJoin('logtranspro.per_empresapersonas as pep', function ($join) use ($fechaIni, $fechaFin) {
                    $join->on('pep.PE_ID_PE', '=', 'ce.pe_id')
                        ->where('pep.TP_ID', '=', 11)
                        ->whereNotIn('pep.PE_ID_EMP', [6761])
                        ->whereRaw("TRUNC(pep.FECINI) <= TRUNC(TO_DATE(?, 'YYYY/MM/DD'))", [$fechaFin]) 
                        ->whereRaw("(TRUNC(pep.FECFIN) >= TRUNC(TO_DATE(?, 'YYYY/MM/DD')) OR pep.FECFIN IS NULL)", [$fechaIni])
                        ->where('pep.ACTIVO', '=', 1)
                        ->where('pep.ESTBORRADO', '=', 0);
                })
                ->leftJoin('logtranspro.per_personas as asoc', 'asoc.id', '=', 'pep.PE_ID_EMP') 
                ->select(
                    'p.identificacion',
                    'pcp.codigo',
                    DB::raw("p.PNOMBRE || NVL(' ' || p.SNOMBRE, '') || ' ' || p.PAPELLIDO || NVL(' ' || p.SAPELLIDO, '') as nombre_completo"),
                    'ce.anotacion as evento',
                    'ce.fechaevento as fecha_evento',
                    DB::raw("(SELECT s.NOMSUCURSAL FROM per_personas s WHERE s.id = ce.Empcreacion) AS agencia_registra_evento"),
                    DB::raw("
                        CASE 
                            WHEN pep.carcliente IS NULL OR pep.carcliente = '0' THEN 'TURNADOR'
                            ELSE pep.carcliente
                        END as vehiculo
                    "),     
                    DB::raw("pcp.ASOCIADO as nombre_asociado")
                )
                ->whereRaw("TRUNC(ce.FECHAEVENTO) >= TRUNC(TO_DATE(?, 'YYYY/MM/DD'))", [$fechaIni])
                ->whereRaw("TRUNC(ce.FECHAEVENTO) <= TRUNC(TO_DATE(?, 'YYYY/MM/DD'))", [$fechaFin]);
                
                 if ($request->evento == 0) {
                    $query->whereIn('ce.evento', ['49', '50']);
                } else {
                    $query->whereIn('ce.evento', [$request->evento]);
                }

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
            $resultados = $query->orderBy('p.identificacion', 'asc')
                                 ->orderBy('ce.FECHAEVENTO', 'asc')
                                 ->get();
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
