<?php

namespace App\Http\Controllers;

use App\Models\GESTIONHUMANA\Incapacidad;
use App\Models\GESTIONHUMANA\incapacidadesSeguimiento;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SeguimientoIncapacidadController extends Controller
{
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

            return sweetAlertJson("Seguimiento radicado N° '. $id .' registrado exitosamente", "success",route('gestion-incapacidades.seguimiento.detalle', ['id' => $incapacidadSeguimiento->IncapacidadId]));
    
        }catch(Exception $e){
            Log::error('Error al registrar el seguimiento: ' . $e->getMessage());
            return sweetAlertJson("Error al registrar el seguimiento", "error",route('gestion-incapacidades.seguimiento.detalle', ['id' => $incapacidadSeguimiento->IncapacidadId]));
        }
    }
}
