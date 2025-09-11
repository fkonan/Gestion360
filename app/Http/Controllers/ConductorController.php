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
            'observacion' => 'required',
            'fecha' => ['required','date']
        ], [
            'identificacion.regex' => 'El campo identificación no tiene un formato valido',
            'identificacion.required' => 'El campo identificación es obligatorio.',
            'observacion.required' => 'Debe ingresar una observación'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $respuesta = $eventoConductorService->registrarEventoDescanso($request);
        return $respuesta;
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

        if(!$persona){
            return response()->json([
                'evento' => null,
                'fecha' => null
            ]);
        }

        $ultimoEvento = PerConductoresEventos::where('pe_id', $persona->id)
            ->where('estborrado', 0)
            ->whereIn('evento', [49, 50]) // Eventos relacionados con descanso
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimoEvento) {
            $fechaEvento = Carbon::parse($ultimoEvento->fechaevento);
            $haceSeisMeses = now()->subMonths(6);

            if ($fechaEvento->lte($haceSeisMeses)) {
            return response()->json([
                'evento' => null,
                'fecha' => null
            ]);
            }

            return response()->json([
            'nombre' => trim($persona->pnombre . ' ' . $persona->psnombre . ' ' . $persona->papellido . ' ' . $persona->sapellido),
            'evento' => $ultimoEvento->anotacion,
            'fecha' => $fechaEvento->format('d/m/Y H:i')
            ]);
        }
        
        return response()->json([
            'evento' => null,
            'fecha' => null
        ]);
    }

}
