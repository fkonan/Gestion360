<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Parametros;
use App\Models\GESTIONHUMANA\Arl;
use App\Models\GESTIONHUMANA\Enfermedades;
use App\Models\GESTIONHUMANA\Eps;
use App\Models\GESTIONHUMANA\Incapacidad;
use App\Rules\IncapacidadMaxima;
use App\Services\BloqueoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IncapacidadController extends Controller
{
    public function listaIncapacidades(){
        $incapacidadesPorGestionar = Incapacidad::where('IncapacidadEstado', '=', 'RADICADO')->exists();

        if($incapacidadesPorGestionar == null){
           /*  return toast("No hay incapacidades radicadas para gestionar","danger",redirect()->route('gestion-incapacidades.index')); */
            return redirect()->back()->with('alert', [
                'type' => 'error', 
                'title' => 'No hay incapacidades radicadas para gestionar'
            ]);


        }
        return view("incapacidades.index");
    }

    public function cargarDatos(){
        $incapacidades = Incapacidad::with(['causa', 'eps','diagnostico','arl'])
            ->where('IncapacidadEstado', '=', 'RADICADO')
            ->get()
            ->map(function ($item) {
                return [
                    'IncPerNom' => $item->IncPerNom,
                    'PerNumDoc' => $item->PerNumDoc,                    
                    'causaDes'  => $item->causa->ParDes ?? '',
                    'DiagnosticoCod' => $item->diagnostico->CodigoCie ?? '',
                    'epsNombre' => $item->eps->EPSNombre ?? '',
                    'arlNombre' => $item->arl->ARLNombre ?? '',
                    'TipoIncapacidad' => $item->IncTipo,
                    'IncFecIni' => $item->IncFecIni,
                    'IncFecFin' => $item->IncFecFin,
                    'DiagnosticoDes' => $item->diagnostico->DescCie ?? '',
                    'IncapacidadEstado' => $item->IncapacidadEstado,
                    'IncFecReg' => $item->IncFecReg,
                    'IncHorReg' => $item->IncHorReg,
                    'IdIncapacidad' => $item->IdIncapacidad,
                    'RevisionDatos' => $item->RevisionDatos,
                ];
            });

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

            return toastModal("Se han actualizado los datos del radicado exitosamente", "success",$redirect);
    
        }catch(Exception $e){
            Log::error('Error al actualizar la incapacidad: ' . $e->getMessage());
            return toastModal("Error al actualizar la incapacidad", "error",$redirect);
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
                return toastModal("Se ha RECHAZADO el radicado ". $id ." exitosamente", "success",route('gestion-incapacidades.incapacidades'));
            }

            $incapacidad->fill($request->all());
            $incapacidad->Observacion = 'RECIBIDO Y APROBADO';
            
            /* Si es aprobada se genera el bloqueo si es conductor*/
            $bloqueo = $bloqueoService->bloqNovedadLogtransInc($incapacidad->IdPerOracle,$incapacidad);

            if ($bloqueo == "bloqueado") {
                $incapacidad->save();
                return toastModal("El radicado ". $id ." ha sido APROBADO exitosamente y se ha generado el bloqueo en Logtrans", "success",route('gestion-incapacidades.incapacidades'));
            }else if($bloqueo === "error"){
                $incapacidad->refresh();
                return toastModal("Error al generar el bloqueo en Logtrans, por favor verifique la información", "error",route('gestion-incapacidades.incapacidades'));
            }else{ 
                $incapacidad->save();
                return toastModal("El radicado ". $id ." ha sido APROBADO exitosamente", "success",route('gestion-incapacidades.incapacidades'));
            }
        }catch(Exception $e){
            Log::error('Error al actualizar el estado de la incapacidad: ' . $e->getMessage());
            return toastModal("Error al actualizar el estado de la incapacidad", "error",route('gestion-incapacidades.incapacidades'));
        }
    }
}
