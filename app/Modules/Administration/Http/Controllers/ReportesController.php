<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\FICS\Boleterias;
use App\Models\GESTIONADMIN\Reporteador;
use App\Modules\Administration\Services\Reportes\ReporteActDatosService as ReportesReporteActDatosService;
use App\Modules\Administration\Services\Reportes\ReportePoliticasService;
use App\Modules\Administration\Services\Reportes\ReportesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;

class ReportesController extends Controller
{
  // vista general para el formulario de reportes, aca se ingresan los parametros
  public function mostrarFormulario($id)
  {
    $reporte = Reporteador::select('parametros', 'origen_db')->findOrFail($id);
    $origen_db = $reporte->origen_db;

    // Decodificar parámetros
    $parametrosArray = json_decode($reporte->parametros, true) ?? [];
    $parametros = array_column($parametrosArray, 'nombre');

    // Agencias para consultas en FICS
    if ($parametros && in_array('paramAgencia', $parametros)) {
      $agencias = DB::connection('oracle')->select("
        SELECT
            n.codigo AS codigo,
            n.nomsucursal AS agencia
        FROM per_personas n
        LEFT JOIN (
            SELECT
                ep.pe_id_emp,
                ep.codigo,
                ep.pe_id_pe,
                ROW_NUMBER() OVER (PARTITION BY ep.pe_id_emp ORDER BY ep.fecini DESC) AS rn
            FROM per_empresapersonas ep
            WHERE ep.tp_id = '14'
              AND ep.activo = '1'
              AND ep.estborrado = '0'
        ) agente_actual
            ON agente_actual.pe_id_emp = n.id
            AND agente_actual.rn = 1
        LEFT JOIN per_personas persona_agente
            ON persona_agente.id = agente_actual.pe_id_pe
            AND persona_agente.estborrado = '0'
            AND persona_agente.estado = 'ACTIVO'
        LEFT JOIN per_personas empresa_rel
            ON empresa_rel.id = agente_actual.pe_id_emp
        LEFT JOIN per_personas admon
            ON admon.id = empresa_rel.pe_id_admon
        WHERE n.identificacion = '890200928'
          AND n.estborrado = '0'
          AND n.nomsucursal != ' '
          AND n.codigo != ' '
          AND n.estado = 'ACTIVO'
        ORDER BY n.codigo
    ");
    }

    // Asegurar que $agencias esté definido incluso si no se carga
    $agencias = $agencias ?? [];

    return view('reportes.formulario', compact('id', 'parametros', 'agencias', 'origen_db'));
  }

  // bootstrap table con los resultados del reporte
  public function show(Request $request)
  {
    // Validacion campos obligatorios
    $validator = Validator::make($request->all(), [
      'id' => 'required|exists:reporteador,id',
      'fechaInicio' => 'date',
      'fechaFin' => 'date|after_or_equal:fechaInicio',
    ], [
      'id.required' => 'El reporte es obligatorio.',
      'id.exists' => 'El reporte no existe.',
      'fechaInicio.date' => 'La fecha de inicio debe ser una fecha válida.',
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

    //Area del reporte
    $areaReporte = $reporte->area;

    // Busca el área en la configuración (según el nombre)
    $areaConfig = collect(config('reportes.areas'))
      ->firstWhere('nombre', $areaReporte);

    if ($areaConfig) {
      $ruta = route($areaConfig['ruta'], ['area' => $areaConfig['nombre']]);
    } else {
      $ruta = url()->previous();
    }

    return view('reportes.tabla', compact('id', 'nombreReporte', 'nombreDocExcel', 'params', 'ruta'));
  }


  // Cargar datos de los reportes con la API
  public function data(Request $request, ReportesService $reportesService)
  {
    try {
      // Obtener todos los parámetros dinámicos que no sean null
      $params = collect($request->all())
        ->reject(fn($value) => $value === null || $value === 'null')
        ->toArray();

      $data = $reportesService->obtenerDatosReporte($params);

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
  public function getReportes(ReportesService $reportesService)
  {
    $reportes = $reportesService->tiposReporte();

    return view('reportes.index', compact('reportes'));
  }

  public function reportesPorArea($area, ReportesService $reportesService)
  {
    try {
      $resultado = $reportesService->obtenerReportesPorArea($area);

      if ($area == 'personas') {
        return view('reportes.personas.index', [
          'reportes' => $resultado['reportes'],
          'area' => $area
        ]);
      }

      $areaFormateada = ucfirst(str_replace('_', ' ', $area));

      return view('reportes.reportesArea', [
        'reportes' => $resultado['reportes'],
        'area' => $areaFormateada
      ]);
    } catch (AuthorizationException $e) {
      abort(403, $e->getMessage());
    } catch (\Exception $e) {
      abort(500, 'Error al obtener los reportes');
    }
  }


  /*
   Reportes que no estan en el reporteador
  */
  //1. Actualizacion de datos personales RRHH
  public function reporteActualizacionDatos()
  {
    return view('reportes.personas.actualizacionDatos');
  }

  public function filtrarActualizacionDatos(Request $request, ReportesReporteActDatosService $reporteActDatosService)
  {
    $validator = Validator::make($request->all(), [
      'filtro' => 'required',
    ], [
      'filtro.required' => 'El filtro es obligatorio.',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      $filtro = $request->input('filtro');
      $actDatos = $reporteActDatosService->obtenerData($filtro);

      session(['actDatos' => $actDatos]);

      return toastModal(
        "Se han encontrado {$actDatos->count()} registros",
        "success",
        route('lista.actDatos')
      );
    } catch (Exception $e) {
      return toastModal($e->getMessage(), "error");
    }
  }


  public function listaActualizacionDatos()
  {
    return view('reportes.personas.listaActualizacionDatos');
  }

  public function cargarDataActualizacionDatos()
  {
    return session('actDatos') ?? [];
  }

  //2. Firma politicas de la empresa RRHH
  public function reporteFirmaPoliticas()
  {
    return view('reportes.empleados.firmaPoliticas');
  }

  public function filtrarfirmaPoliticas(Request $request, ReportePoliticasService $ReportePoliticasService)
  {
    $validator = Validator::make($request->all(), [
      'fechaInicio' => 'date',
      'fechaFin' => 'date|after_or_equal:fechaInicio',
    ], [
      'fechaInicio.date' => 'La fecha de inicio debe ser una fecha válida.',
      'fechaFin.date' => 'La fecha de fin debe ser una fecha válida.',
      'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
      $filtros = $request->only(['fechaInicio', 'fechaFin', 'tipoFiltro', 'valorFiltro']);
      $firmas = $ReportePoliticasService->obtenerFirmas($filtros);

      session(['firmas' => $firmas]);

      return toastModal(
        "Se han encontrado {$firmas->count()} registros para las fechas seleccionadas",
        "success",
        route('lista.firmaPoliticas')
      );
    } catch (Exception $e) {
      return toastModal($e->getMessage(), "error");
    }
  }

  public function listaFirmasPoliticas()
  {
    return view('reportes.empleados.listaFirmasPoliticas');
  }

  public function cargarDataFirmaPoliticas()
  {
    return session('firmas') ?? [];
  }
}
