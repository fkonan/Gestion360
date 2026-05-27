<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Reporteador;
use App\Modules\Administration\Services\Reportes\ReporteActDatosService as ReportesReporteActDatosService;
use App\Modules\Administration\Services\Reportes\ReportePoliticasService;
use App\Modules\Administration\Services\Reportes\ReportesService;
use App\Modules\GestionWeb\Models\FitTipoVehiculos;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportesController extends Controller
{
    private const REPORTES_RANGO_MAXIMO_MESES = [
        7 => 6,
        99 => 1,
    ];

    private const REPORTES_SIN_LIMITE_FECHAS = [
        9,
    ];

    private const REPORTES_VISTA_LIMITADA = [
        21 => 3000,
        99 => 3000,
    ];

    private const REPORTES_EXPORTACION_STREAM = [
        21,
        99,
    ];

    // vista general para el formulario de reportes, aca se genera el formulario en base a los parametros
    public function mostrarFormulario($id)
    {
        $reporte = Reporteador::select('parametros', 'origen_db')->findOrFail($id);
        $origen_db = $reporte->origen_db;

        // Decodificar parámetros
        $parametrosArray = json_decode($reporte->parametros, true) ?? [];

        // Indexar por nombre
        $parametros = collect($parametrosArray)->keyBy('nombre');

        // Hay algún parámetro activo
        $tieneFechaInicio = isset($parametros['paramFechaInicio']);
        $tieneFechaFin = isset($parametros['paramFechaFin']);
        $limiteMeses = $this->obtenerLimiteMesesReporte((int) $id);

        $hayParametros = $parametros->isNotEmpty();

        // Mensaje de cabecera reporte
        if (! $tieneFechaInicio && ! $tieneFechaFin) {
            $mensajeCabecera = $hayParametros
              ? 'Este reporte no requiere de un rango de fechas.'
              : 'Este reporte no requiere parámetros.';
        } elseif ($limiteMeses === null) {
            $mensajeCabecera = 'Este reporte no tiene restriccion maxima en el rango de fechas.';
        } elseif ($limiteMeses > 1) {
            $mensajeCabecera = "El rango de fechas no puede ser mayor a {$limiteMeses} meses.";
        } else {
            $mensajeCabecera = 'El rango de fechas no puede ser mayor a 30 días.';
        }

        // ======== CATEGORÍAS VEHÍCULO ========
        $categorias = isset($parametros['paramCategoriaVehiculo'])
          ? FitTipoVehiculos::select('servicio')
              ->whereIn('descripcion', ['BUS', 'BUSETA', 'MICROBUS'])
              ->get()
          : [];

        // ======== AGENCIAS ========
        $agencias = isset($parametros['paramAgencia'])
          ? DB::connection('oracle')->select("
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
        ")
          : [];

        return view('administration::reportes.formulario', [
            'id' => $id,
            'parametros' => $parametros->toArray(),
            'agencias' => $agencias,
            'origen_db' => $origen_db,
            'categorias' => $categorias,
            'mensajeCabecera' => $mensajeCabecera,
        ]);
    }

    // bootstrap table con los resultados del reporte
    public function show(Request $request, ReportesService $reportesService)
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

        // Validar rango de fechas segun la configuracion del reporte
        $id = $request->input('id');
        $reporte = Reporteador::findOrFail($id);
        $limiteMeses = $this->obtenerLimiteMesesReporte((int) $id);

        $fechaInicio = Carbon::parse($request->fechaInicio);
        $fechaFin = Carbon::parse($request->fechaFin);

        if ($limiteMeses !== null) {
            $fechaMaxima = $fechaInicio->copy()->addMonthsNoOverflow($limiteMeses);

            if ($fechaInicio->diffInMonths($fechaFin) > $limiteMeses || $fechaFin->gt($fechaMaxima)) {
                $mensajeRango = $limiteMeses > 1 ? "{$limiteMeses} meses" : '1 mes';

                return sweetAlert("El rango entre las fechas no puede ser mayor a {$mensajeRango}.", 'error');
            }
        }

        $nombreReporte = $reporte->nombre;
        $nombreDocExcel = normalizarNombre($nombreReporte);

        $params = $request->all();
        $params['fechaInicio'] = $fechaInicio->toDateString();
        $params['fechaFin'] = $fechaFin->toDateString();

        // Area del reporte
        $areaReporte = $reporte->area;

        // Busca slug y ruta a partir del nombre de área almacenado en BD
        $rutaArea = $reportesService->obtenerRutaAreaPorNombre($areaReporte);
        $ruta = $rutaArea['ruta'] ?? url()->previous();

        return view('administration::reportes.tabla', compact('id', 'nombreReporte', 'nombreDocExcel', 'params', 'ruta'));
    }

    private function obtenerLimiteMesesReporte(int $reporteId): ?int
    {
        $sinLimite = array_map('intval', (array) config(
            'reporteador.reportes_rango_fechas.sin_limite',
            self::REPORTES_SIN_LIMITE_FECHAS
        ));

        if (in_array($reporteId, $sinLimite, true)) {
            return null;
        }

        $maxMesesPorReporte = (array) config(
            'reporteador.reportes_rango_fechas.max_meses_por_reporte',
            self::REPORTES_RANGO_MAXIMO_MESES
        );

        $limiteDefault = max(1, (int) config('reporteador.reportes_rango_fechas.default_meses', 1));
        $limite = $maxMesesPorReporte[$reporteId] ?? $limiteDefault;

        return max(1, (int) $limite);
    }

    // Cargar datos de los reportes con la API
    public function data(Request $request, ReportesService $reportesService)
    {
        try {
            @set_time_limit(300);
            // Obtener todos los parámetros dinámicos que no sean null
            $params = collect($request->all())
                ->reject(fn ($value) => $value === null || $value === 'null')
                ->toArray();

            $idReporte = (int) ($params['id'] ?? $params['idReporte'] ?? 0);
            if (isset(self::REPORTES_VISTA_LIMITADA[$idReporte])) {
                $maxRows = (int) self::REPORTES_VISTA_LIMITADA[$idReporte];
                $data = $reportesService->obtenerDatosReporteLimitado($params, $maxRows);

                return response()->json([
                    'total' => count($data),
                    'rows' => $data,
                    'preview_limited' => true,
                    'max_rows' => $maxRows,
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }

            $data = $reportesService->obtenerDatosReporte($params);

            // Respuesta en formato Bootstrap Table
            return response()->json([
                'total' => count($data),
                'rows' => $data,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Error al obtener el reporte '.($request->id ?? '-').': '.$e->getMessage());

            return response()->json([
                'errors' => ['general' => ['Error al obtener el reporte']],
            ], 500);
        }
    }

    public function exportarCsv(Request $request, ReportesService $reportesService)
    {
        @set_time_limit(0);

        $params = collect($request->all())
            ->reject(fn ($value) => $value === null || $value === 'null')
            ->toArray();

        $idReporte = (int) ($params['id'] ?? $params['idReporte'] ?? 0);
        if (! in_array($idReporte, self::REPORTES_EXPORTACION_STREAM, true)) {
            abort(403, 'Este reporte no tiene exportacion masiva habilitada.');
        }

        $reporte = Reporteador::findOrFail($idReporte);
        $filename = normalizarNombre($reporte->nombre).'_'.now()->format('Ymd_His').'.csv';

        $rows = $reportesService->obtenerCursorReporte($params);
        if (! is_iterable($rows)) {
            abort(500, 'No fue posible generar el archivo.');
        }

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // BOM UTF-8 para apertura correcta en Excel.
            fwrite($out, "\xEF\xBB\xBF");

            $headerWritten = false;
            $lineas = 0;

            foreach ($rows as $row) {
                $fila = is_array($row) ? $row : (array) $row;

                if (! $headerWritten) {
                    fputcsv($out, array_keys($fila), ';');
                    $headerWritten = true;
                }

                $valores = array_map(static function ($value) {
                    if ($value === null) {
                        return '';
                    }

                    if ($value instanceof \DateTimeInterface) {
                        return $value->format('Y-m-d H:i:s');
                    }

                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }

                    if (is_scalar($value)) {
                        return (string) $value;
                    }

                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                }, $fila);

                fputcsv($out, $valores, ';');

                $lineas++;
                if (($lineas % 500) === 0) {
                    fflush($out);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    // metodo que retorna la vista principal de reportes
    public function getReportes(ReportesService $reportesService)
    {
        $reportes = $reportesService->tiposReporte();

        return view('administration::reportes.index', compact('reportes'));
    }

    public function reportesPorArea($area, ReportesService $reportesService)
    {
        try {
            $resultado = $reportesService->obtenerReportesPorArea($area);

            if ($area == 'personas') {
                return view('administration::reportes.personas.index', [
                    'reportes' => $resultado['reportes'],
                    'area' => $area,
                ]);
            }

            $areaFormateada = ucfirst(str_replace('_', ' ', $area));

            return view('administration::reportes.reportesArea', [
                'reportes' => $resultado['reportes'],
                'area' => $areaFormateada,
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
    // 1. Actualizacion de datos personales RRHH
    public function reporteActualizacionDatos()
    {
        return view('administration::reportes.personas.actualizacionDatos');
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
                'success',
                route('lista.actDatos')
            );
        } catch (Exception $e) {
            return toastModal($e->getMessage(), 'error');
        }
    }

    public function listaActualizacionDatos()
    {
        return view('administration::reportes.personas.listaActualizacionDatos');
    }

    public function cargarDataActualizacionDatos()
    {
        return session('actDatos') ?? [];
    }

    // 2. Firma politicas de la empresa RRHH
    public function reporteFirmaPoliticas()
    {
        return view('administration::reportes.empleados.firmaPoliticas');
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
                'success',
                route('lista.firmaPoliticas')
            );
        } catch (Exception $e) {
            return toastModal($e->getMessage(), 'error');
        }
    }

    public function listaFirmasPoliticas()
    {
        return view('administration::reportes.empleados.listaFirmasPoliticas');
    }

    public function cargarDataFirmaPoliticas()
    {
        return session('firmas') ?? [];
    }
}
