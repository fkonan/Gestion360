<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\FICS\Tripulantes;
use App\Models\GESTIONPASAJES\Cargos;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\GESTIONPASAJES\Preoperacionales;
use App\Models\LOGTRANS\PerConductoresEventos;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\BloqueoService;
use App\Services\EventoConductorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConductorController extends Controller
{
    public function reportePreoperacionales(){
        return view("reportes.conductores.preoperacionales.form");
    }

    public function preoperacionArchivo($id){
        $archivo = Preoperacionales::findOrFail($id);

        return response($archivo->contenido)
            ->header('Content-Type', $archivo->mime);
    }

    public function filtrarPreoperacionales(Request $request){
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
        ], [
            'identificacion.regex' => 'El campo identificación no tiene un formato valido',
            'identificacion.required' => 'El campo identificación es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $preoperacional = Preoperacionales::where("documento",$request->identificacion)
            ->orderBy("created_at","desc")
            ->first();

        if(!$preoperacional){
            return toastModal("No existen registros para el número de documento", "info");
        }

        return response()->json([
            'success' => true,
            'html'    => view("reportes.conductores.preoperacionales.adjunto", compact("preoperacional"))->render()
        ]);
    }

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

    public function revisionPreoperacional(){
        return view('conductores.revisionPreoperacional');
    }

    public function novedadPreoperacional(Request $request){
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
            'adjunto' => 'required|file|max:2048|mimes:jpg,jpeg,png'
        ], [
            'identificacion.regex'    => 'El campo identificación no tiene un formato valido.',
            'identificacion.required' => 'El campo identificación es obligatorio.',
            'adjunto.required'        => 'El adjunto es obligatorio.',
            'adjunto.file'            => 'El adjunto debe ser un archivo válido.',
            'adjunto.max'             => 'El adjunto no debe superar los 2MB.',
            'adjunto.mimes'           => 'El adjunto debe ser una imagen (jpg, jpeg, png).'
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            //Adjunto
            $file = $request->file('adjunto');

            $preoperacional = new Preoperacionales();
            $preoperacional->documento  = $request->identificacion;
            $preoperacional->nombre     = $file->getClientOriginalName();
            $preoperacional->mime       = $file->getMimeType();
            $preoperacional->contenido  = file_get_contents($file->getRealPath());
            
            $resp = BloqueoService::levantarBloqueoFICS($request->identificacion, BloqueoService::ID_BLOQUEO_FICS_PREOPERACIONAL);

            if($resp){
                $preoperacional->save();
                return toastModal("Se levanto el bloqueo correctamente", "success",route('gestion-incapacidades.index'));  
            }else{
                return toastModal("El conductor no presenta ningun bloqueo activo", "info",route('gestion-incapacidades.index'));  
            }
        }catch(Exception $e){
            Log::error('Error al levantar la novedad de preoperacional ' . $e->getMessage());
            return toastModal("Error al levantar la novedad de preoperacional", "success",route('gestion-incapacidades.index'));  
        }
        
    }

}
