<?php

namespace App\Http\Controllers;

use App\Constants\Permisos;
use App\Models\FICS\Boleterias;
use App\Models\GESTIONADMIN\Reporteador;
use App\Services\ApiReportes;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportesController extends Controller
{
  //vista general para el formulario de reportes, aca se ingresan los parametros
  public function mostrarFormulario($id)
  {
    $reporte = Reporteador::select('parametros', 'origen_db')->findOrFail($id);
    $origen_db = $reporte->origen_db;

    // Decodificar parámetros
    $parametrosArray = json_decode($reporte->parametros, true) ?? [];
    $parametros = array_column($parametrosArray, 'nombre');

    // Agencias para consultas en FICS
    $agenciasFICS = Boleterias::select('nombre', 'codigo')->get();

    return view('reportes.formulario', compact('id', 'parametros', 'agenciasFICS', 'origen_db'));
  }

  // bootstrap table con los resultados del reporte
  public function show(Request $request)
  {
    //Validacion campos obligatorios
    $validator = Validator::make($request->all(), [
      'id' => 'required|exists:reporteador,id',
      'fechaInicio' => 'required|date',
      'fechaFin' => 'required|date|after_or_equal:fechaInicio',
    ], [
      'id.required' => 'El reporte es obligatorio.',
      'id.exists' => 'El reporte no existe.',
      'fechaInicio.required' => 'La fecha de inicio es obligatoria.',
      'fechaInicio.date' => 'La fecha de inicio debe ser una fecha válida.',
      'fechaFin.required' => 'La fecha de fin es obligatoria.',
      'fechaFin.date' => 'La fecha de fin debe ser una fecha válida.',
      'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
    ]);

    if ($validator->fails()) {
      return sweetAlert($validator->errors()->first(), 'error');
    }

    // Validar rango de máximo 1 mes (el reporte 9 ignora esta condición)
    $fechaInicio = Carbon::parse($request->fechaInicio);
    $fechaFin = Carbon::parse($request->fechaFin);

    if ($request->id != 9) {
      if ($fechaInicio->diffInMonths($fechaFin) > 1 || $fechaFin->gt($fechaInicio->copy()->addMonth())) {
        return sweetAlert('El rango entre las fechas no puede ser mayor a 1 mes.', 'error');
      }
    }

    $id = $request->input('id');
    $reporte = Reporteador::findOrFail($id);

    $nombreReporte = $reporte->nombre;
    $nombreDocExcel = normalizarNombre($nombreReporte);

    // Para el caso del reporte 9 el rango de fechas se amplia un mes
    if ($id == 9) {
      $fechaInicio = $fechaInicio->copy()->subMonth();
      $fechaFin = $fechaFin->copy()->addMonth();
    }

    $params = $request->all();
    $params['fechaInicio'] = $fechaInicio->toDateString();
    $params['fechaFin'] = $fechaFin->toDateString();

    return view('reportes.tabla', compact('id', 'nombreReporte', 'nombreDocExcel', 'params'));
  }


  // Cargar datos de los reportes con la API
  public function data(Request $request, ApiReportes $apiReportes)
  {
    try {
      // Obtener todos los parámetros dinámicos que no sean null
      $params = collect($request->all())
        ->reject(fn($value) => $value === null || $value === 'null')
        ->toArray();

      // Normalizamos el id para la API
      if (isset($params['id'])) {
        $params['idReporte'] = $params['id'];
        unset($params['id']);
      }

      $reporte = Reporteador::findOrFail($params['idReporte']);

      // Consultar API
      $data = $apiReportes->obtenerReporte($params);

      if (!$data || empty($data)) {
        return response()->json([
          'total' => 0,
          'rows' => []
        ]);
      }

      // Incrementar contador de consultas
      $reporte->increment('total_consultas');

      // Respuesta en formato Bootstrap Table
      return response()->json([
        'total' => count($data),
        'rows' => $data
      ]);
    } catch (Exception $e) {
      Log::error('Error al obtener el reporte ' . ($request->id ?? '-') . ': ' . $e->getMessage());

      return response()->json([
        'errors' => ['general' => ['Error al obtener el reporte']]
      ], 500);
    }
  }

  //metodo que retorna la vista principal de reportes
  public function getReportes()
  {
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
      [
        'titulo' => 'Reportes de Carga',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de carga.',
        'ruta' => 'reportes.carga',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_CARGA,
        'icono' => 'fa-solid fa-truck-loading'
      ],
    ];

    return view('reportes.index', compact('reportes'));
  }

  public function reportesConductores()
  {
    return view("reportes.conductores.index");
  }

  public function reportesPasajes()
  {
    return view("reportes.pasajes.index");
  }

  public function reportesCarga()
  {
    return view("reportes.carga.index");
  }

  public function reportesEmpleados()
  {
    return view("reportes.empleados.index");
  }
}
