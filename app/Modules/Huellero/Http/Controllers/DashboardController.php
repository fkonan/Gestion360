<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Huellero\Services\DigitalPersonaService;
use App\Modules\Huellero\Services\EventoService;
use App\Modules\Huellero\Services\HuellaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private $eventoService;

    private $huellaService;

    private $digitalPersonaService;

    public function __construct(
        EventoService $eventoService,
        HuellaService $huellaService,
        DigitalPersonaService $digitalPersonaService
    ) {
        $this->eventoService = $eventoService;
        $this->huellaService = $huellaService;
        $this->digitalPersonaService = $digitalPersonaService;
    }

    /**
     * Mostrar dashboard principal
     */
    public function index()
    {
        try {
            // Obtener estadísticas generales
            $estadisticasEventos = $this->eventoService->obtenerEstadisticas();
            $estadisticasHuellas = $this->huellaService->obtenerEstadisticas();

            // Verificar estado del servicio
            $servicioDisponible = $this->digitalPersonaService->isServiceAvailable();

            // Obtener eventos recientes
            $eventosRecientes = $this->eventoService->obtenerEventosHoy()->take(100);

            return view('huellero::dashboard.index', compact(
                'estadisticasEventos',
                'estadisticasHuellas',
                'servicioDisponible',
                'eventosRecientes'
            ));
        } catch (\Exception $e) {
            Log::error('Error en dashboard huellero', ['error' => $e->getMessage()]);

            return view('huellero::dashboard.index', [
                'estadisticasEventos' => [],
                'estadisticasHuellas' => [],
                'servicioDisponible' => false,
                'eventosRecientes' => collect([]),
                'error' => 'Error al cargar el dashboard',
            ]);
        }
    }

    /**
     * Obtener datos en tiempo real para el dashboard (AJAX)
     */
    public function datosRealTime()
    {
        try {
            $estadisticas = $this->eventoService->obtenerEstadisticas();
            $eventosRecientes = $this->eventoService->obtenerEventosHoy()->take(5);
            $servicioDisponible = $this->digitalPersonaService->isServiceAvailable();

            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas,
                'eventos_recientes' => $eventosRecientes->map(function ($evento) {
                    return [
                        'id' => $evento->id,
                        'persona' => $evento->persona->nombreCompleto(),
                        'identificacion' => $evento->persona->identificacion,
                        'tipo_evento' => $evento->tipo_evento,
                        'fecha_evento' => $evento->fechaevento->format('H:i:s'),
                        'tipo_registro' => $evento->tipo_registro_texto,
                        'icono' => $evento->icono_evento,
                        'clase_registro' => $evento->clase_registro,
                    ];
                }),
                'servicio_disponible' => $servicioDisponible,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos en tiempo real', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos en tiempo real',
            ], 500);
        }
    }

    /**
     * Obtener dispositivos conectados
     */
    public function dispositivos()
    {
        try {
            $dispositivos = $this->digitalPersonaService->getConnectedDevices();

            return response()->json([
                'success' => true,
                'dispositivos' => $dispositivos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo dispositivos', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener información de un dispositivo específico
     */
    public function infoDispositivo(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
        ]);

        try {
            $info = $this->digitalPersonaService->getDeviceInfo($request->device_id);

            return response()->json([
                'success' => true,
                'info' => $info,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo info del dispositivo', [
                'device_id' => $request->device_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del dispositivo',
            ], 500);
        }
    }
}
