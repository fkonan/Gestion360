<?php

namespace App\Http\Controllers\Huellero;

use App\Http\Controllers\Controller;
use App\Services\Huellero\EventoService;
use App\Models\LOGTRANS\PerPersonas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventosController extends Controller
{
    private $eventoService;

    public function __construct(EventoService $eventoService)
    {
        $this->eventoService = $eventoService;
    }

    /**
     * Mostrar lista de eventos
     */
    public function index()
    {
        try {
            // Obtener estadísticas del día
            $estadisticas = $this->eventoService->obtenerEstadisticas();

            return view('huellero.eventos.index', compact('estadisticas'));

        } catch (\Exception $e) {
            Log::error('Error cargando interfaz de eventos', ['error' => $e->getMessage()]);

            return view('huellero.eventos.index', [
                'estadisticas' => [],
                'error' => 'Error al cargar los eventos'
            ]);
        }
    }

    /**
     * Obtener eventos para DataTable (AJAX)
     */
    public function cargarDatos(Request $request)
    {
        try {
            $fechaInicio = $request->get('fecha_inicio', today()->format('Y-m-d'));
            $fechaFin = $request->get('fecha_fin', today()->format('Y-m-d'));
            $tipoEvento = $request->get('tipo_evento');
            $tipoRegistro = $request->get('tipo_registro');

            $query = \App\Models\Huellero\PerPersonasEventos::with('persona')
                ->activos()
                ->whereBetween('fechaevento', [
                    Carbon::parse($fechaInicio)->startOfDay(),
                    Carbon::parse($fechaFin)->endOfDay()
                ]);

            // Filtros opcionales
            if ($tipoEvento && in_array($tipoEvento, ['49', '50'])) {
                $query->where('evento', $tipoEvento);
            }

            if ($tipoRegistro && in_array($tipoRegistro, ['0', '1'])) {
                $query->where('tiporegistro', $tipoRegistro);
            }

            $eventos = $query->orderBy('fechaevento', 'desc')->get();

            $data = $eventos->map(function($evento) {
                return [
                    'id' => $evento->id,
                    'identificacion' => $evento->persona->identificacion ?? 'N/A',
                    'nombre_completo' => $evento->persona->nombreCompleto() ?? 'N/A',
                    'fechaevento' => $evento->fechaevento->format('d/m/Y H:i:s'),
                    'tipo_evento' => $evento->tipo_evento,
                    'tipo_registro' => $evento->tipo_registro_texto,
                    'anotacion' => $evento->anotacion,
                    'observacion' => $evento->observacion,
                    'mensaje_error' => $evento->mensaje_error,
                    'icono_evento' => $evento->icono_evento,
                    'clase_registro' => $evento->clase_registro,
                    'es_manual' => $evento->esManual()
                ];
            });

            return response()->json([
                'data' => $data,
                'recordsTotal' => $data->count(),
                'recordsFiltered' => $data->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error cargando datos de eventos', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'error' => 'Error al cargar los datos'
            ]);
        }
    }

    /**
     * Obtener eventos de una persona específica
     */
    public function eventosPorPersona(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:PER_PERSONAS,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date'
        ]);

        try {
            $fechaInicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio) : now()->subDays(30);
            $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : now();

            $eventos = $this->eventoService->obtenerEventosPersona(
                $request->persona_id,
                $fechaInicio,
                $fechaFin
            );

            $persona = PerPersonas::find($request->persona_id);

            return response()->json([
                'success' => true,
                'persona' => [
                    'id' => $persona->id,
                    'identificacion' => $persona->identificacion,
                    'nombre' => $persona->nombreCompleto()
                ],
                'eventos' => $eventos->map(function($evento) {
                    return [
                        'id' => $evento->id,
                        'fecha' => $evento->fechaevento->format('d/m/Y H:i:s'),
                        'tipo_evento' => $evento->tipo_evento,
                        'tipo_registro' => $evento->tipo_registro_texto,
                        'observacion' => $evento->observacion,
                        'mensaje_error' => $evento->mensaje_error,
                        'es_manual' => $evento->esManual()
                    ];
                }),
                'estadisticas' => [
                    'total_eventos' => $eventos->count(),
                    'entradas' => $eventos->where('evento', '49')->count(),
                    'salidas' => $eventos->where('evento', '50')->count(),
                    'automaticos' => $eventos->where('tiporegistro', 0)->count(),
                    'manuales' => $eventos->where('tiporegistro', 1)->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo eventos por persona', [
                'persona_id' => $request->persona_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los eventos'
            ], 500);
        }
    }

    /**
     * Generar reporte de asistencia
     */
    public function reporteAsistencia(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date'
        ]);

        try {
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            $reporte = $this->eventoService->obtenerReporteAsistencia($fechaInicio, $fechaFin);

            return response()->json([
                'success' => true,
                'reporte' => collect($reporte)->map(function($item) {
                    return [
                        'persona' => [
                            'id' => $item['persona']->id,
                            'identificacion' => $item['persona']->identificacion,
                            'nombre' => $item['persona']->nombreCompleto(),
                            'codigo' => $item['persona']->codigo
                        ],
                        'total_entradas' => $item['total_entradas'],
                        'total_salidas' => $item['total_salidas'],
                        'errores' => $item['errores'],
                        'eventos' => $item['eventos']->map(function($evento) {
                            return [
                                'fecha' => $evento->fechaevento->format('d/m/Y H:i:s'),
                                'tipo' => $evento->tipo_evento,
                                'automatico' => $evento->esAutomatico()
                            ];
                        })
                    ];
                }),
                'periodo' => [
                    'inicio' => $fechaInicio->format('d/m/Y'),
                    'fin' => $fechaFin->format('d/m/Y')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando reporte de asistencia', [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function estadisticasRealTime()
    {
        try {
            $estadisticas = $this->eventoService->obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas,
                'timestamp' => now()->format('H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas en tiempo real', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * Exportar eventos a Excel/CSV
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'formato' => 'required|in:excel,csv'
        ]);

        try {
            $fechaInicio = Carbon::parse($request->fecha_inicio);
            $fechaFin = Carbon::parse($request->fecha_fin);

            $eventos = \App\Models\Huellero\PerPersonasEventos::with('persona')
                ->activos()
                ->whereBetween('fechaevento', [$fechaInicio->startOfDay(), $fechaFin->endOfDay()])
                ->orderBy('fechaevento', 'desc')
                ->get();

            // Preparar datos para exportar
            $data = $eventos->map(function($evento) {
                return [
                    'ID' => $evento->id,
                    'Identificación' => $evento->persona->identificacion ?? 'N/A',
                    'Nombre Completo' => $evento->persona->nombreCompleto() ?? 'N/A',
                    'Fecha y Hora' => $evento->fechaevento->format('d/m/Y H:i:s'),
                    'Tipo Evento' => $evento->tipo_evento,
                    'Tipo Registro' => $evento->tipo_registro_texto,
                    'Anotación' => $evento->anotacion,
                    'Observación' => $evento->observacion,
                    'Error' => $evento->mensaje_error ?? 'N/A'
                ];
            });

            if ($request->formato === 'csv') {
                return $this->exportarCSV($data, $fechaInicio, $fechaFin);
            } else {
                return $this->exportarExcel($data, $fechaInicio, $fechaFin);
            }

        } catch (\Exception $e) {
            Log::error('Error exportando eventos', [
                'formato' => $request->formato,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar los datos'
            ], 500);
        }
    }

    /**
     * Exportar a CSV
     */
    private function exportarCSV($data, $fechaInicio, $fechaFin)
    {
        $filename = "eventos_huellero_{$fechaInicio->format('Y-m-d')}_al_{$fechaFin->format('Y-m-d')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // BOM para UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Headers
            if (!$data->isEmpty()) {
                fputcsv($file, array_keys($data->first()), ';');
            }

            // Data
            foreach ($data as $row) {
                fputcsv($file, array_values($row), ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar a Excel (simulado como CSV con formato Excel)
     */
    private function exportarExcel($data, $fechaInicio, $fechaFin)
    {
        // Para una implementación completa de Excel, usar Laravel Excel package
        return $this->exportarCSV($data, $fechaInicio, $fechaFin);
    }
}
