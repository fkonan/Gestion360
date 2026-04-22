<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Huellero\Services\EventoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificacionController extends Controller
{
    private $eventoService;

    public function __construct(EventoService $eventoService)
    {
        $this->eventoService = $eventoService;
    }

    /**
     * Verificar huella dactilar
     */
    public function verificarHuella(Request $request)
    {
        $request->validate([
            'template_huella' => 'required|string',
        ]);

        try {
            $resultado = $this->eventoService->procesarEventoPorHuella($request->template_huella);

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $resultado['message'],
                    'data' => [
                        'persona' => [
                            'id' => $resultado['persona']->id,
                            'identificacion' => $resultado['persona']->identificacion,
                            'nombre' => $resultado['persona']->nombreCompleto(),
                            'codigo' => $resultado['persona']->codigo,
                        ],
                        'evento' => [
                            'id' => $resultado['evento']->id,
                            'tipo' => $resultado['evento']->tipo_evento,
                            'fecha' => $resultado['evento']->fechaevento->format('d/m/Y H:i:s'),
                            'automatico' => $resultado['evento']->esAutomatico(),
                        ],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $resultado['message'],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error en verificacion de huella', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno en la verificacion',
            ], 500);
        }
    }

    /**
     * Obtener estado actual del sistema
     */
    public function estadoSistema()
    {
        try {
            $estadisticas = $this->eventoService->obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas,
                'timestamp' => now()->format('d/m/Y H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estado del sistema', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado del sistema',
            ], 500);
        }
    }
}
