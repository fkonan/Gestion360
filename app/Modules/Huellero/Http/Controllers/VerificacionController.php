<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Services\EventoService;
use App\Modules\Huellero\Services\HuellaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerificacionController extends Controller
{
    private $huellaService;

    private $eventoService;

    public function __construct(HuellaService $huellaService, EventoService $eventoService)
    {
        $this->huellaService = $huellaService;
        $this->eventoService = $eventoService;
    }

    /**
     * Mostrar interfaz de verificación
     */
    public function index()
    {
        try {
            // Obtener configuración
            $formatos = config('fingerprint.supported_formats');
            $tiposError = config('fingerprint.tipos_error');

            return view('huellero::verificacion.index', compact('formatos', 'tiposError'));

        } catch (\Exception $e) {
            Log::error('Error cargando interfaz de verificación', ['error' => $e->getMessage()]);

            return view('huellero::verificacion.index', [
                'formatos' => [],
                'tiposError' => [],
                'error' => 'Error al cargar la interfaz',
            ]);
        }
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
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'],
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error en verificación de huella', [
                'template_length' => strlen($request->template_huella),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno en la verificación',
            ], 500);
        }
    }

    /**
     * Verificar huella de persona específica
     */
    public function verificarPersona(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:PER_PERSONAS,id',
            'template_huella' => 'required|string',
        ]);

        try {
            $verificado = $this->huellaService->verificarHuellaPersona(
                $request->persona_id,
                $request->template_huella
            );

            $persona = PerPersonas::find($request->persona_id);

            if ($verificado) {
                return response()->json([
                    'success' => true,
                    'verified' => true,
                    'message' => 'Huella verificada correctamente',
                    'persona' => [
                        'id' => $persona->id,
                        'identificacion' => $persona->identificacion,
                        'nombre' => $persona->nombreCompleto(),
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'verified' => false,
                    'message' => 'La huella no coincide con la persona especificada',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error verificando huella de persona', [
                'persona_id' => $request->persona_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la verificación',
            ], 500);
        }
    }

    /**
     * Registrar evento manual por error
     */
    public function registrarEventoManual(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:PER_PERSONAS,id',
            'tipo_evento' => 'required|in:entrada,salida',
            'razon_error' => 'required|string',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            $evento = $this->eventoService->registrarEventoManual(
                $request->persona_id,
                $request->tipo_evento,
                $request->razon_error,
                Auth::id() ?? 1,
                $request->observacion
            );

            $persona = PerPersonas::find($request->persona_id);

            return response()->json([
                'success' => true,
                'message' => 'Evento manual registrado correctamente',
                'data' => [
                    'evento_id' => $evento->id,
                    'persona' => $persona->nombreCompleto(),
                    'tipo_evento' => $evento->tipo_evento,
                    'fecha' => $evento->fechaevento->format('d/m/Y H:i:s'),
                    'razon_error' => $evento->mensaje_error,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error registrando evento manual', [
                'persona_id' => $request->persona_id,
                'tipo_evento' => $request->tipo_evento,
                'razon_error' => $request->razon_error,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el evento manual',
            ], 500);
        }
    }

    /**
     * Buscar persona por identificación
     */
    public function buscarPersona(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string',
        ]);

        try {
            $persona = PerPersonas::where('identificacion', $request->identificacion)
                ->where('estado', 'ACTIVO')
                ->first();

            if (! $persona) {
                return response()->json([
                    'success' => false,
                    'message' => 'Persona no encontrada',
                ], 404);
            }

            // Obtener información adicional
            $huellas = $this->huellaService->obtenerHuellasPersona($persona->id);
            $ultimoEvento = $persona->ultimoEvento();

            return response()->json([
                'success' => true,
                'persona' => [
                    'id' => $persona->id,
                    'identificacion' => $persona->identificacion,
                    'nombre' => $persona->nombreCompleto(),
                    'codigo' => $persona->codigo,
                    'estado_actual' => $persona->estado_actual,
                    'tiene_huellas' => $persona->tieneHuellas(),
                    'total_huellas' => $huellas->count(),
                    'ultimo_evento' => $ultimoEvento ? [
                        'tipo' => $ultimoEvento->tipo_evento,
                        'fecha' => $ultimoEvento->fechaevento->format('d/m/Y H:i:s'),
                        'automatico' => $ultimoEvento->esAutomatico(),
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error buscando persona', [
                'identificacion' => $request->identificacion,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
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
