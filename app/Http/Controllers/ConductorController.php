<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\FICS\Tripulantes;
use App\Models\GESTIONADMIN\Reporteador;
use App\Models\GESTIONPASAJES\Cargos;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\LOGTRANS\PerConductoresEventos;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\ApiReportes;
use App\Services\EventoConductorService;
use App\Services\IngresoSalidaConducService;
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
        return view("reportes.conductores.firmas.firmaPolEquipaje");
    }


    public function filtrarFirmaEquipaje(Request $request){
        $validator = Validator::make($request->all(), [
            'codigo' => ['nullable', 'numeric'],
            'identificacion' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $columns = [
                DB::raw('CodCon as codigo'),
                DB::raw('DocCon as identificacion'),
                DB::raw('NomCon as nombre_completo'),
                DB::raw('FirFecReg as fecha_registro'),
                DB::raw('FirHorReg as hora_registro')
            ];

            $query = FirmaPoliticas::select($columns);

            if ($request->filled('codigo')) {
                $query->where("CodCon", $request->codigo);
            }

            if ($request->filled('identificacion')) {
                $query->where("DocCon", $request->identificacion);
            }

            $listaFirmas = $query->get();

            if ($listaFirmas->isEmpty()) {
                return toastModal("No se encontraron resultados para los parametros ingresados.", "warning", "#");
            } else {
                $params = http_build_query($request->only(['codigo','identificacion']));
                return toastModal(
                    'Se han encontrado ' . $listaFirmas->count() . ' registros para los parámetros seleccionados',
                    "success",
                    route("lista.firmaEquipaje") . "?" . $params
                );
            }

        } catch (Exception $e) {
            Log::error('Error al obtener la lista de firmas politica equipaje: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados", "error");
        }
    }


    public function listaFirmasEquipaje(){
        return view('reportes.conductores.firmas.politicaEquipaje');
    }

    public function cargarDataFirmaEquipaje(Request $request){
        $columns = [
            DB::raw('CodCon as codigo'),
            DB::raw('DocCon as identificacion'),
            DB::raw('NomCon as nombre_completo'),
            DB::raw('FirFecReg as fecha_registro'),
            DB::raw('FirHorReg as hora_registro')
        ];

        $query = FirmaPoliticas::select($columns);

        if ($request->filled('codigo')) {
            $query->where("CodCon", $request->codigo);
        }

        if ($request->filled('identificacion')) {
            $query->where("DocCon", $request->identificacion);
        }

        return $query->get();
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
        return view("reportes.conductores.descansos.formIngresosSalidas");
    }

    public function listaIngSalConductores(){
        return view("reportes.conductores.descansos.ingresosSalidas");
    }


    /* public function reporteIngSalConductores(Request $request, IngresoSalidaConducService $ingresoSalidaConducService){
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
            $fechaIni = Carbon::parse($request->fechaInicial)->subMonth()->format('Y/m/d');
            $fechaFin = Carbon::parse($request->fechaFinal)->addMonth()->format('Y/m/d');
            $parametroInput = $request->parametroInput;
            $filtro = $request->filtro;

            $eventos = ($request->evento == 0) ? ['49', '50'] : [$request->evento];

            $sql = "SELECT
                p.identificacion,
                pcp.codigo,
                p.PNOMBRE || NVL(' ' || p.SNOMBRE, '') || ' ' || p.PAPELLIDO || NVL(' ' || p.SAPELLIDO, '') AS nombre_completo,
                ce.anotacion AS evento,
                ce.fechaevento AS fecha_evento,
                (SELECT s.NOMSUCURSAL FROM per_personas s WHERE s.id = ce.Empcreacion) AS agencia_registra_evento,
                CASE 
                    WHEN pep.carcliente IS NULL OR pep.carcliente = '0' THEN 'TURNADOR'
                    ELSE pep.carcliente
                END AS vehiculo,
                pcp.ASOCIADO AS nombre_asociado
            FROM
                LOGTRANSPRO.per_conductoreseventos ce
            LEFT JOIN
                logtranspro.per_personas p ON p.id = ce.pe_id
            JOIN
                logtranspro.per_contrato_persona pcp ON pcp.pe_id_pe = ce.pe_id
            LEFT JOIN
                logtranspro.per_empresapersonas pep ON pep.PE_ID_PE = ce.pe_id
                AND pep.TP_ID = 11
                AND pep.PE_ID_EMP NOT IN (6761)
                AND TRUNC(pep.FECINI) <= TRUNC(TO_DATE(:fechaFin, 'YYYY/MM/DD'))
                AND (TRUNC(pep.FECFIN) >= TRUNC(TO_DATE(:fechaIni, 'YYYY/MM/DD')) OR pep.FECFIN IS NULL)
                AND pep.ACTIVO = 1
                AND pep.ESTBORRADO = 0
            LEFT JOIN
                logtranspro.per_personas asoc ON asoc.id = pep.PE_ID_EMP
            WHERE
                ce.EMPCREACION != 1124882226
                AND TRUNC(ce.FECHAEVENTO) >= TRUNC(TO_DATE(:fechaIni, 'YYYY/MM/DD'))
                AND TRUNC(ce.FECHAEVENTO) <= TRUNC(TO_DATE(:fechaFin, 'YYYY/MM/DD'))
                AND ce.evento IN (:evento1, :evento2) 
                AND (
                    (:filtro = 'identificacion' AND p.identificacion LIKE :parametroInput)
                    OR (:filtro = 'codigo' AND pcp.codigo LIKE :parametroInput)
                    OR (:filtro = 'todos' AND p.identificacion LIKE '%' AND pcp.codigo LIKE '%')
                )
            ORDER BY
                p.identificacion ASC,
                ce.FECHAEVENTO ASC
            ";

            $resultados = DB::connection('oracle')->select($sql, [
                'fechaIni' => $fechaIni,
                'fechaFin' => $fechaFin,
                'evento1' => $eventos[0],
                'evento2' => $eventos[1] ?? $eventos[0], // si no hay segundo evento
                'parametroInput' => $parametroInput,
                'filtro' => $filtro,
            ]);

            // Se formatea el reporte con el servicio
            $coleccionFinal = $ingresoSalidaConducService->formatoEspecialReporte($resultados);
            $numeroRegistros = count($coleccionFinal);

            if ($numeroRegistros == 0) {
                return toastModal("No se encontraron resultados para los parametros ingresados.", "warning","#");
            }

            session(['ingSalConductores' => $coleccionFinal]);
            return toastModal("Registros encontrados: ".$numeroRegistros ,"success",route("lista.ingresoSalidas"));
        }catch(Exception $e){
            Log::error('Error al obtener la lista de ingreso salida de conductores: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados","danger");
        }
    } */

    public function reporteIngSalConductores(Request $request, ApiReportes $apiReportes){
        $validator = Validator::make($request->all(), [
            'fechaInicial' => 'date|required',
            'fechaFinal' => [
                'date',
                'required',
                'after_or_equal:fechaInicial',
            ],
        ], [
            'fechaFinal.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'fechaInicial.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fechaInicial.required' => 'El campo fecha de inicio es obligatorio.',
            'fechaFinal.required' => 'El campo fecha de fin es obligatorio.',
            'fechaFinal.date' => 'La fecha de fin debe ser una fecha válida.',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $fechaIni = Carbon::parse($request->fechaInicial)->subMonth()->format('Y-m-d');
            $fechaFin = Carbon::parse($request->fechaFinal)->addMonth()->format('Y-m-d');
            $tipoFiltro = $request->filtro;
            $filtroValor = $request->parametroInput;
            
            //Consulta el reporte por la API
            $resultados = $apiReportes->obtenerReporte([
                'idReporte'   => 9,
                'fechaInicio' => $fechaIni,
                'fechaFin'    => $fechaFin,
                'tipoFiltro'  => $tipoFiltro,
                'valorFiltro' => $filtroValor
            ]);

            //Formato especial de las fechas
            $camposFecha = ['FECHA_SALIDA', 'FECHA_REINTEGRO'];

            $resultados = collect($resultados)->map(function ($item) use ($camposFecha) {
                foreach ($camposFecha as $campo) {
                    if (!empty($item[$campo])) {
                        $item[$campo] = Carbon::parse($item[$campo])
                            ->timezone('America/Bogota')
                            ->format('d/m/Y');
                            /* ->format('d/m/Y H:i'); */
                    }
                }
                return $item;
            })->toArray();

            $totalResultados = count($resultados);

            if ($totalResultados == 0) {
                return toastModal("No se encontraron resultados para los parametros ingresados.", "warning","#");
            }

            //Aumentar el contador de consultas del reporte
            $reporte = Reporteador::findOrFail(9);
            $reporte->increment('total_consultas');

            session(['ingSalConductores' => $resultados]);
            return toastModal("Registros encontrados: ".$totalResultados ,"success",route("lista.ingresoSalidas"));
        }catch(Exception $e){
            Log::error('Error al obtener la lista de ingreso salida de conductores: ' . $e->getMessage());
            return toastModal("Error al obtener los resultados","danger");
        }
    }

    public function cargarDataIngSalConductores(){
        $data = session('ingSalConductores') ?? [] ;
        return $data;
    }

    static public function funcionesCargo($cargoNombre){
        $cargo = Cargos::with('funciones')
            ->where('CarNom', $cargoNombre)
            ->first();

        return $cargo->funciones()->get();
    }

    public function obtenerUltimoEventoDescanso(Request $request)
    {
        $identificacion = $request->identificacion;

        $persona = PerPersonas::where('identificacion', $identificacion)->first();

        $ultimoEvento = PerConductoresEventos::where('pe_id', $persona->id)
            ->where('estborrado', 0)
            ->whereIn('evento', [25, 49, 50]) // Eventos relacionados con descanso
            /* ->orderBy('fechaevento', 'desc') */
            ->orderBy('id', 'desc')
            ->first();
        
        if ($ultimoEvento) {
            return response()->json([
                'nombre' => $persona->pnombre . ' ' . $persona->psnombre . ' ' . $persona->papellido . ' ' . $persona->sapellido,
                'evento' => $ultimoEvento->anotacion,
                'fecha' => date('d/m/Y H:i', strtotime($ultimoEvento->fechaevento))
            ]);
        }
        
        return response()->json([
            'evento' => null,
            'fecha' => null
        ]);
    }
}
