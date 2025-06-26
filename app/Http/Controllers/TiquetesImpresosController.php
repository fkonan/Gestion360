<?php

namespace App\Http\Controllers;

use App\Models\GESTIONPASAJES\TiquetesImpresos;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TiquetesImpresosController extends Controller
{
    public function fechasReporte(){
        $totalTiquetes = TiquetesImpresos::count();
        return view('tiquetes.fechasReporte',compact('totalTiquetes'));
    }

    public function filtrarTiquetes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'agencia' => 'required',
            ], [
                'fechaInicio.required' => 'La fecha de inicio es obligatoria',
                'fechaInicio.date' => 'La fecha de inicio no es válida',
                'fechaFin.required' => 'La fecha de fin es obligatoria',
                'fechaFin.date' => 'La fecha de fin no es válida',
                'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio',
                'agencia.required' => 'La agencia es obligatoria',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                $query = TiquetesImpresos::whereBetween('ImpFecReg', [$request->fechaInicio, $request->fechaFin]);

                if ($request->agencia !== 'todas') {
                    $query->where('Agencia', $request->agencia);
                }

                $tiquetes = $query->get()->map(function ($item) {
                    return [
                        'NumDocPer' => $item->NumDocPer,
                        'NumeroPasaje' => $item->NumeroPasaje,
                        'TerminalOrigen' => $item->TerminalOrigen,
                        'TerminalDestino' => $item->TerminalDestino,
                        'FechaSalida' => Carbon::parse($item->FechaSalida)->format('Y-m-d H:i'),
                        'NumerodeViaje' => $item->NumerodeViaje,
                        'PrecioBase' => number_format($item->PrecioBase, 0, ',', ''),
                        'Descuento' => number_format($item->Descuento, 0, ',', ''),
                        'PrecioTotal' => number_format($item->PrecioTotal, 0, ',', ''),
                        'Asiento' => $item->Asiento,
                        'Agencia' => $item->Agencia,
                        'ImpFecReg' => $item->ImpFecReg,
                        'ImpHorReg' => $item->ImpHorReg,
                    ];
                });

                $numeroTiquetes = $tiquetes->count();
    
                if ($tiquetes->isEmpty()) {
                    return toastModal("No se han encontrado tiquetes para las fechas seleccionadas", "warning");
                }
    
                session(['tiquetes' => $tiquetes]);
                return toastModal("Se han encontrado " .$numeroTiquetes. " tiquetes para las fechas seleccionadas", "success", route('reportes.listaTiquetes'));
                
            }catch(Exception $e){
                Log::error('Error al filtrar los tiquetes: ' . $e->getMessage());
                return toastModal("Error al filtrar los tiquetes", "error",route('reportes.index'));
            }
        }

    public function listaTiquetes(){
        return view('tiquetes.listaTiquetes');
    }

    public function cargarDataTiquetes(){
        $tiquetes = session('tiquetes') ?? [] ;
        return $tiquetes;
    }
}
