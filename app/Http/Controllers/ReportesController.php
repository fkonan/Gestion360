<?php

namespace App\Http\Controllers;

use App\Constants\Permisos;
use App\Models\GESTIONADMIN\Reporteador;
use App\Services\ApiReportes;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportesController extends Controller
{   
    //vista general para el formulario de reportes, aca se ingresan los parametros
    public function mostrarFormulario($id)
    {
        return view('reportes.formulario', compact('id'));
    }


    //metodo que recibe los parametros del formulario y obtiene el reporte
    public function obtenerReporte($id, Request $request, ApiReportes $apiReportes)
    {
        $validator = Validator::make($request->all(), [
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ],[
            'fechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'fechaInicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fechaFin.required' => 'La fecha de fin es obligatoria.',
            'fechaFin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $fechaInicio = $request->fechaInicio;
            $fechaFin = $request->fechaFin;

            //se valida que el reporte exista
            $reporte = Reporteador::findOrFail($id);
            if (!$reporte) {
                return toastModal("Reporte no encontrado","warning");
            }

            //se obtiene el reporte a traves del servicio ApiReportes
            $data = $apiReportes->obtenerReporte($id, $fechaInicio, $fechaFin);
            if (!$data) {
                return toastModal("No se encontraron resultados","warning");
            }

            return response()->json([
                'success' => true,
                'html' => view('reportes.resultado', [
                    'dataJson' => json_encode($data),
                    'registros' => count($data),
                    'nombreReporte' => normalizarNombre($reporte->nombre),
                ])->render(),
                'registros' => count($data)
            ]);

        }catch(Exception $e){
            Log::error('Error al obtener el reporte ' . $id . ': ' . $e->getMessage());
            return toastModal("Error al obtener el reporte","error");
        }
    }

    //metodo que retorna la vista principal de reportes
    public function getReportes(){
        $reportes = [
            [
                'titulo' => 'Reportes de Conductores',
                'descripcion' => 'Consultar',
                'tooltip' => 'Incluye diversos reportes relacionados con la gestión y actividad de los conductores.',
                'ruta' => 'reportes.conductores',
                'permiso' => Permisos::ADMINISTRACION_REPORTES_CONDUCTORES,
                'icono' => 'fa-solid fa-users'
            ],
            [
                'titulo' => 'Reportes de Pasajes',
                'descripcion' => 'Consultar',
                'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de pasajes, tiquetes y esquemas tarifarios.',
                'ruta' => 'reportes.pasajes',
                'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
                'icono' => 'fa-solid fa-ticket-alt'
            ],
        ];
        return view('reportes.index',compact('reportes'));
    }

    public function reportesConductores(){
        return view("reportes.conductores.index");
    }

    public function reportesPasajes(){
        return view("reportes.pasajes.index");
    }
}
