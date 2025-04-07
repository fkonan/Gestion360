<?php

namespace App\Http\Controllers;

use App\Models\TiquetesImpresos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TiquetesImpresosController extends Controller
{
    public function fechasReporte(){
        $totalTiquetes = TiquetesImpresos::count();
        return view('tiquetes.fechasReporte',compact('totalTiquetes'));
    }

    public function filtrarTiquetes(Request $request){
        $validator = Validator::make($request->all(), [
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'agencia' => 'required',
        ],[
            'fechaInicio.required' => 'La fecha de inicio es obligatoria',
            'fechaInicio.date' => 'La fecha de inicio no es una fecha válida',
            'fechaFin.required' => 'La fecha de fin es obligatoria',
            'fechaFin.date' => 'La fecha de fin no es una fecha válida',
            'fechaFin.after_or_equal' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio',
            'agencia.required' => 'La agencia es obligatoria',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $query = TiquetesImpresos::whereBetween('ImpFecReg', [$request->fechaInicio, $request->fechaFin]);

        if ($request->agencia !== 'todas') {
            $query->where('Agencia', $request->agencia);
        }

        $tiquetes = $query->get();
        $numeroTiquetes = $tiquetes->count();

        if ($tiquetes->isEmpty()) {
            return response()->json([
                'redirect' => '#',
                'type' => 'warning', 
                'title' => 'No se han encontrado tiquetes para las fechas seleccionadas',
            ]); 
        }

        session(['tiquetes' => $tiquetes]);
        return response()->json([
            'redirect' => route('reportes.listaTiquetes'),
            'type' => 'success', 
            'title' => 'Se han encontrado ' . $numeroTiquetes . ' tiquetes para las fechas seleccionadas',
        ]);
    }

    public function listaTiquetes(){
        return view('tiquetes.listaTiquetes');
    }

    public function cargarDataTiquetes(){
        $tiquetes = session('tiquetes');
        return $tiquetes;
    }
}
