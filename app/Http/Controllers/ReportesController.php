<?php

namespace App\Http\Controllers;

class ReportesController extends Controller
{
    public function getReportes(){
        $reportes = [
            [
                'titulo' => 'Reportes de Conductores',
                'descripcion' => 'Incluye diversos reportes relacionados con la gestión y actividad de los conductores.',
                'ruta' => 'reportes.conductores',
                'permiso' => 'administracion.reportes.reportes_conductores'
            ],
            [
                'titulo' => 'Reportes de Pasajes',
                'descripcion' => 'Incluye reportes relacionados con la gestión y análisis de pasajes, tiquetes y esquemas tarifarios.',
                'ruta' => 'reportes.pasajes',
                'permiso' => 'administracion.reportes.reportes_pasajes'
            ],
        ];
        return view('reportes.main',compact('reportes'));
    }

    public function reportesConductores(){
        return view("reportes.conductores.main");
    }

    public function reportesPasajes(){
        return view("reportes.pasajes.main");
    }
}
