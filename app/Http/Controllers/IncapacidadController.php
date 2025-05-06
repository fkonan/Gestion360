<?php

namespace App\Http\Controllers;

use App\Models\GESTIONHUMANA\Arl;
use App\Models\GESTIONHUMANA\Enfermedades;
use App\Models\GESTIONHUMANA\Eps;
use App\Models\GESTIONHUMANA\Incapacidad;
use App\Models\GESTIONHUMANA\incapacidadesSeguimiento;
use App\Models\Parametros;
use App\Rules\IncapacidadMaxima;
use App\Services\BloqueoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncapacidadController extends Controller
{
    public function listaIncapacidades(){
        $incapacidadesPorGestionar = Incapacidad::where('IncapacidadEstado', '=', 'RADICADO')->exists();

        if($incapacidadesPorGestionar == null){
            session()->flash('alert', ['type' => 'success','title' => 'No hay incapacidades radicadas para gestionar']);
            return redirect()->route('gestion-incapacidades.index');
        }
        return view("incapacidades.listaIncapacidades");
    }

    public function cargarDatos(){
        $incapacidades = Incapacidad::with(['causa', 'diagnostico', 'eps', 'arl'])
            ->where('IncapacidadEstado', '=', 'RADICADO')
            ->get();
        return $incapacidades;
    }

    public function incapacidadesSeguimiento(){
        $incapacidadesSeguimiento = Incapacidad::with(['causa', 'diagnostico', 'eps', 'arl']);

        if($incapacidadesSeguimiento == null){
            session()->flash('alert', ['type' => 'success','title' => 'No hay incapacidades para hacer seguimiento']);  
            return back();
        }
        return view("incapacidades.listaSeguimiento",compact("incapacidadesSeguimiento"));
    }

    public function cargarDatosSeguimiento() {
        $incapacidades = Incapacidad::with(['causa', 'diagnostico', 'eps', 'arl'])
            ->where('IncapacidadEstado', '=', 'APROBADO')
            ->get();
        return $incapacidades;
    }

    public function incapacidadAdjuntos($id){
        $incapacidad = Incapacidad::findOrFail($id)->load(['documentos']); 
        $incapacidadDocumentos = $incapacidad->documentos()->get();

        if($incapacidadDocumentos->isEmpty()){
            return '<div class="alert alert-danger">No hay documentos adjuntos para esta incapacidad</div>';
        }
        return view("incapacidades.adjuntos",compact("incapacidadDocumentos"));
    }

    public function editIncapacidad($id){
        $incapacidad = Incapacidad::findOrFail($id)->load(['causa', 'diagnostico', 'eps', 'arl']);
        $causasIncapacidad = Parametros::where('ParNomGru', 'CAUSA-INCAPACIDAD')->get();
        $codigosIncapacidad = Enfermedades::pluck('CodigoCie', 'IdEnfermedad');
        $listaEps = Eps::pluck('EPSNombre', 'IdEPS');
        $listaArl = Arl::pluck('ARLNombre', 'IdARL');

        return view("incapacidades.revisionIncapacidad",compact("incapacidad","causasIncapacidad","listaEps","listaArl","codigosIncapacidad"));
    }

    public function updateIncapacidad(Request $request, $id){
        $incapacidad = Incapacidad::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'IncFecIni' => 'date',
            'IncFecFin' => [
                'date',
                'after_or_equal:IncFecIni',
                new IncapacidadMaxima($request->IncFecIni),
            ],
        ],[
            'IncFecFin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',  
            'IncFecIni.date' => 'La fecha de fin debe ser una fecha válida.',
            'IncFecFin.date' => 'La fecha de fin debe ser una fecha válida.',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            //Valida de que ruta viene (incapacidad o seguimiento)
            $referer = $request->headers->get('referer');
            $redirect = route('gestion-incapacidades.incapacidades');

            $incapacidad->update($request->all());
    
            if ($referer == route('gestion-incapacidades.seguimiento')) {
                $redirect = route('gestion-incapacidades.seguimiento');
            }
    
            return response()->json([
                'title' => 'Se han actualizado los datos del radicado exitosamente',
                'redirect' => $redirect,
                'type' => 'success',
            ]);
        }catch(Exception $e){
            Log::error('Error al actualizar la incapacidad: ' . $e->getMessage());
            return response()->json([
                'title' => 'Error al actualizar la incapacidad',
                'redirect' => $redirect,
                'type' => 'error', 
            ]);
        }
    }

    public function gestionIncapacidad($id){
        $incapacidad = Incapacidad::findOrFail($id);
        return view("incapacidades.gestionIncapacidad",compact("incapacidad"));
    }

    public function updateEstadoIncapacidad(Request $request, $id, BloqueoService $bloqueoService){
        $incapacidad = Incapacidad::findOrFail($id);
        
        try{
            if ($request->IncapacidadEstado == "RECHAZADO") {
                $validator = Validator::make($request->all(), [
                    'Observacion' => 'required|max:255',
                ],[
                    'Observacion.required' => 'El campo observación es obligatorio.',
                    'Observacion.max' => 'La observación no puede exceder los 255 caracteres.',
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'errors' => $validator->errors()
                    ], 422);
                }
    
                $incapacidad->update($request->all());
    
                return response()->json([
                    'title' => 'Se ha RECHAZADO el radicado '. $id .' exitosamente',
                    'redirect' => route('gestion-incapacidades.incapacidades'),
                    'type' => 'success', 
                ]);
            }

            /* Si es aprobada se genera el bloqueo si es conductor*/
            $incapacidad->fill($request->all());
            $incapacidad->Observacion = 'RECIBIDO Y APROBADO';
            
            $bloqueo = $bloqueoService->bloqNovedadLogtransInc($incapacidad->IdPerOracle,$incapacidad);

            if ($bloqueo == "bloqueado") {
                $incapacidad->save();
                return response()->json([
                    'title' => 'El radicado '. $id .' ha sido APROBADO exitosamente y se ha generado el bloqueo en Logtrans',
                    'redirect' => route('gestion-incapacidades.incapacidades'),
                    'type' => 'success', 
                ]);
            }else if($bloqueo === "error"){
                $incapacidad->refresh();
                return response()->json([
                    'title' => 'Error al generar el bloqueo en Logtrans, por favor verifique la información',
                    'redirect' => route('gestion-incapacidades.incapacidades'),
                    'type' => 'error', 
                ]);
            }else{ 
                $incapacidad->save();
                return response()->json([
                    'title' => 'El radicado '. $id .' ha sido APROBADO exitosamente',
                    'redirect' => route('gestion-incapacidades.incapacidades'),
                    'type' => 'success', 
                ]);
            }

        }catch(Exception $e){
            Log::error('Error al actualizar el estado de la incapacidad: ' . $e->getMessage());
            return response()->json([
                'title' => 'Error al actualizar el estado de la incapacidad',
                'redirect' => route('gestion-incapacidades.incapacidades'),
                'type' => 'error', 
            ]);
        }
    }

    public function seguimientoDetalle($id){
        $incapacidad = Incapacidad::findOrFail($id);
        $listaSeguimiento = $incapacidad->seguimiento()
            ->orderBy('SegFecReg', 'desc')
            ->orderBy('SegHorReg', 'desc')
            ->get();  
        return view("incapacidades.registroSeguimiento",compact("incapacidad","listaSeguimiento"));
    }

    public function nuevoSeguimiento($id){
        $incapacidad = Incapacidad::findOrFail($id);
        return view("incapacidades.nuevoSeguimiento",compact("incapacidad"));
    }

    public function guardarSeguimiento(Request $request, $id){

        try{
            $validator = Validator::make($request->all(), [
                'Observacion' =>'required|max:255',
            ],[
                'Observacion.required' => 'El campo observación es obligatorio.',
                'Observacion.max' => 'La observación no puede exceder los 255 caracteres.',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
    
            $incapacidadSeguimiento = new incapacidadesSeguimiento();
            $incapacidadSeguimiento->IncapacidadId = $id;
            $incapacidadSeguimiento->Observacion = strtoupper($request->Observacion);
            $incapacidadSeguimiento->SegFecReg = now();
            $incapacidadSeguimiento->SegHorReg = now();
            $incapacidadSeguimiento->UserRegistra = $user->persona->nombreCompleto();
            $incapacidadSeguimiento->Estado = "ACTIVO";
            $incapacidadSeguimiento->save();
    
            return response()->json([
                'title' => 'Seguimiento radicado N° '. $id .' registrado exitosamente',
                'redirect' => route('gestion-incapacidades.seguimiento.detalle', ['id' => $incapacidadSeguimiento->IncapacidadId]),
                'type' => 'success', 
            ]);
        }catch(Exception $e){
            Log::error('Error al registrar el seguimiento: ' . $e->getMessage());
            return response()->json([
                'title' => 'Error al registrar el seguimiento',
                'redirect' => route('gestion-incapacidades.seguimiento.detalle', ['id' => $incapacidadSeguimiento->IncapacidadId]),
                'type' => 'error', 
            ]);
        }
    }
}
