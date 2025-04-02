<?php

namespace App\Http\Controllers;

use App\Models\Arl;
use App\Models\Eps;
use App\Models\Incapacidad;
use App\Models\Parametros;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncapacidadController extends Controller
{
    public function listaIncapacidades(){
        $incapacidadesPorGestionar = Incapacidad::where('IncapacidadEstado', '=', 'RADICADO')->exists();

        if($incapacidadesPorGestionar == null){
            session()->flash('alert', ['type' => 'success','title' => 'No hay incapacidades radicadas para gestionar']);
            return back();
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
        return view("incapacidades.seguimiento",compact("incapacidadesSeguimiento"));
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
        $listaEps = Eps::all();
        $listaArl = Arl::all();

        return view("incapacidades.datosIncapacidad",compact("incapacidad","causasIncapacidad","listaEps","listaArl"));
    }

    public function updateIncapacidad(Request $request, $id){
        $incapacidad = Incapacidad::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'IncFecFin' => 'after_or_equal:IncFecIni',
        ],[
            'IncFecFin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',  
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $incapacidad->update($request->all());

        return response()->json([
            'title' => 'Se han actualizado los datos del radicado exitosamente',
            'redirect' => route('gestion-incapacidades.incapacidades'),
            'type' => 'success', 
        ]);
       
    }

    public function gestionIncapacidad($id){
        $incapacidad = Incapacidad::findOrFail($id);
        return view("incapacidades.gestionIncapacidad",compact("incapacidad"));
    }

    public function updateEstadoIncapacidad(Request $request, $id){
        $incapacidad = Incapacidad::findOrFail($id);
        
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
                'title' => 'El radicado ha sido rechazado exitosamente',
                'redirect' => route('gestion-incapacidades.incapacidades'),
                'type' => 'success', 
            ]);
        }

        $incapacidad->update($request->all());

        return response()->json([
            'title' => 'El radicado ha sido aprobado exitosamente',
            'redirect' => route('gestion-incapacidades.incapacidades'),
            'type' => 'success', 
        ]);
    }

    public function seguimientoDetalle($id){
        $listaSeguimiento = Incapacidad::findOrFail($id)->seguimiento;
        return view("incapacidades.seguimientoDetalle",compact("listaSeguimiento"));
    }
}
