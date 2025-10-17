<?php

namespace App\Http\Controllers;

use App\Constants\Permisos;
use App\Models\FICS\Boleterias;
use App\Models\GESTIONADMIN\Reporteador;
use App\Models\GESTIONPASAJES\FirmaPoliticas;
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
      /* $reporte->increment('total_consultas'); */


      // Reporte 19: Conductores y empleados sin firma políticas
      /*
        Este reporte muestra los conductores y empleados que no tienen firma en las políticas.
        Por lo que se requiere cargar desde la base de datos de gestión de pasajes cuales son los empleados que tienen firma.
        */
      if ($params['idReporte'] == 19) {
        $empleadosConFirma = FirmaPoliticas::select('DocCon')
          ->where('FirFecReg', '>=', '2025-10-01') // Fecha desde la cual se consideran las firmas
          ->distinct()
          ->pluck('DocCon')
          ->toArray();

        // Filtrar los datos para excluir los empleados que tienen firma
        $data = array_values(array_filter($data, function ($item) use ($empleadosConFirma) {
          return !in_array($item['IDENTIFICACION'], $empleadosConFirma);
        }));
      }

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
        'titulo' => 'Reportes Personas',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye diversos reportes relacionados con la gestión y actividad de los empleados y conductores.',
        'ruta' => 'reportes.personas',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_EMPLEADOS,
        'icono' => 'fa-solid fa-users'
      ],
      [
        'titulo' => 'Reportes Pasajes',
        'descripcion' => 'Consultar',
        'tooltip' => 'Incluye reportes relacionados con la gestión y análisis de pasajes, tiquetes y esquemas tarifarios.',
        'ruta' => 'reportes.pasajes',
        'permiso' => Permisos::ADMINISTRACION_REPORTES_PASAJES,
        'icono' => 'fa-solid fa-ticket-alt'
      ],
      [
        'titulo' => 'Reportes Carga',
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
    $reportesPasajes = Reporteador::where('area', 'Unidad pasajes')
      ->where('estado', 'ACTIVO')
      ->get();
    return view("reportes.pasajes.index", compact('reportesPasajes'));
  }

  public function reportesCarga()
  {
    $reportesCarga = Reporteador::where('area', 'Unidad carga')
      ->where('estado', 'ACTIVO')
      ->get();
    return view("reportes.carga.index", compact('reportesCarga'));
  }

  public function reportesPersonas()
  {
    $reportesPersonas = Reporteador::where('area', 'RRHH')
      ->where('estado', 'ACTIVO')
      ->get();
    return view("reportes.personas.index", compact('reportesPersonas'));
  }

  public function reportesEmpleados()
  {
    return view("reportes.empleados.index");
  }

  /*
   Reportes que no estan en el reporteador
  */

  public function reporteFirmaPoliticas()
  {
    return view('reportes.empleados.firmaPoliticas');
  }

  public function filtrarfirmaPoliticas(Request $request)
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
      $fechaInicio = $request->fechaInicio;
      $fechaFin = $request->fechaFin;

      // Consulta base
      $query = FirmaPoliticas::whereBetween('FirFecReg', [$fechaInicio, $fechaFin])
        ->where('PoliticaId', '!=', 2)
        ->orderBy('FirFecReg', 'desc')
        ->orderBy('FirHorReg', 'desc');

      // Filtros
      if ($request->tipoFiltro === 'identificacion') {
        $query->where('DocCon', $request->valorFiltro);
      } elseif ($request->tipoFiltro === 'codigo') {
        $query->where('CodCon', $request->valorFiltro);
      } elseif ($request->tipoFiltro !== 'todos') {
        return toastModal("Tipo de filtro no válido", "error");
      }

      $data = $query->get();

      if ($data->isEmpty()) {
        return toastModal("No se han encontrado registros para las fechas seleccionadas", "warning");
      }

      // Agrupación y mapeo
      $agrupado = $data
        ->groupBy(fn($item) => $item->DocCon . '|' . $item->CodCon)
        ->flatMap(function ($grupo) {
          $politicasEspeciales = $grupo->whereIn('PoliticaId', [1, 3, 5]);
          $otrasPoliticas = $grupo->whereNotIn('PoliticaId', [1, 3, 5]);

          $resultado = collect();

          // Función para estructurar el formato de salida
          $formatear = fn($item, $nombrePolitica) => [
            'IdFirma' => $item->IdFirma,
            'Código' => $item->CodCon,
            'Documento' => $item->DocCon,
            'Nombre del Empleado' => $item->NomCon,
            'Fecha de Firma' => $item->FirFecReg . ' ' . $item->FirHorReg,
            'Cargo' => $item->Cargo,
            'Correo Electrónico' => $item->Correo,
            'Nombre Política' => $nombrePolitica,
          ];

          if ($politicasEspeciales->isNotEmpty()) {
            $primero = $politicasEspeciales->sortByDesc('FirFecReg')->first();
            $nombreAgrupado = $politicasEspeciales->pluck('nombre_politica')->unique()->join(', ');
            $resultado->push($formatear($primero, $nombreAgrupado));
          }

          foreach ($otrasPoliticas as $item) {
            $resultado->push($formatear($item, $item->nombre_politica));
          }

          return $resultado;
        });

      session(['firmas' => $agrupado]);

      return toastModal(
        "Se han encontrado {$agrupado->count()} registros para las fechas seleccionadas",
        "success",
        route('lista.firmaPoliticas')
      );
    } catch (Exception $e) {
      Log::error('Error al obtener la lista de firmas de empleados: ' . $e->getMessage());
      return toastModal("Error al obtener los resultados", "error");
    }
  }


  public function listaFirmasPoliticas()
  {
    return view('reportes.empleados.listaFirmasPoliticas');
  }

  public function cargarDataFirmaPoliticas()
  {
    $firmas = session('firmas') ?? [];
    return $firmas;
  }
}
