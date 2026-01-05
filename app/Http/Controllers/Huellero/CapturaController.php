<?php

namespace App\Http\Controllers\Huellero;

use App\Exceptions\Huellero\FingerprintCaptureException;
use App\Exceptions\Huellero\DeviceNotConnectedException;
use App\Http\Controllers\Controller;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\Huellero\DigitalPersonaService;
use App\Services\Huellero\HuellaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CapturaController extends Controller
{
    private $digitalPersonaService;
    private $huellaService;

    public function __construct(DigitalPersonaService $digitalPersonaService, HuellaService $huellaService)
    {
        $this->digitalPersonaService = $digitalPersonaService;
        $this->huellaService = $huellaService;
    }

    /**
     * Mostrar interfaz de captura de huellas
     */
    public function index()
    {
        try {
            // Obtener personas disponibles para registro
            $personas = PerPersonas::select('id', 'identificacion', 'pnombre', 'snombre', 'papellido', 'sapellido')
                ->where('estado', 'ACTIVO')
                ->orderBy('identificacion')
                ->get();

            // Verificar servicio disponible
            $servicioDisponible = $this->digitalPersonaService->isServiceAvailable();

            // Obtener configuración de dedos
            $dedos = config('fingerprint.dedos');
            $formatos = config('fingerprint.supported_formats');

            return view('huellero.captura.index', compact(
                'personas',
                'servicioDisponible',
                'dedos',
                'formatos'
            ));

        } catch (\Exception $e) {
            Log::error('Error cargando interfaz de captura', ['error' => $e->getMessage()]);

            return view('huellero.captura.index', [
                'personas' => collect([]),
                'servicioDisponible' => false,
                'dedos' => [],
                'formatos' => [],
                'error' => 'Error al cargar la interfaz de captura'
            ]);
        }
    }

    /**
     * Iniciar captura de huella
     */
    public function iniciarCaptura(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'format' => 'required|string|in:PngImage,Raw,Intermediate,Compressed'
        ]);

        try {
            $resultado = $this->digitalPersonaService->startCapture(
                $request->device_id,
                $request->format
            );

            if (!$resultado) {
                throw new FingerprintCaptureException('No se pudo iniciar la captura');
            }

            return response()->json([
                'success' => true,
                'message' => 'Captura iniciada correctamente'
            ]);

        } catch (DeviceNotConnectedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No hay dispositivos conectados'
            ], 400);

        } catch (FingerprintCaptureException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error iniciando captura', [
                'device_id' => $request->device_id,
                'format' => $request->format,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Detener captura de huella
     */
    public function detenerCaptura(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string'
        ]);

        try {
            $resultado = $this->digitalPersonaService->stopCapture($request->device_id);

            return response()->json([
                'success' => $resultado,
                'message' => $resultado ? 'Captura detenida' : 'Error al detener captura'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deteniendo captura', [
                'device_id' => $request->device_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al detener la captura'
            ], 500);
        }
    }

    /**
     * Procesar huella capturada
     */
    public function procesarCaptura(Request $request)
    {
        $request->validate([
            'samples' => 'required',
            'quality' => 'required|numeric',
            'format' => 'required|string'
        ]);

        try {
            $datosCaptura = [
                'samples' => $request->samples,
                'quality' => $request->quality,
                'format' => $request->format
            ];

            $datosProcesados = $this->digitalPersonaService->processCapturedFingerprint($datosCaptura);

            return response()->json([
                'success' => true,
                'data' => $datosProcesados,
                'message' => 'Huella procesada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando captura', [
                'quality' => $request->quality,
                'format' => $request->format,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la huella capturada'
            ], 500);
        }
    }

    /**
     * Registrar huella en el sistema
     */
    public function registrarHuella(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:PER_PERSONAS,id',
            'dedo' => 'required|string|size:2',
            'template_huella' => 'required|string',
            'quality' => 'required|numeric|min:50'
        ]);

        try {
            $huella = $this->huellaService->registrarHuella(
                $request->persona_id,
                $request->dedo,
                $request->template_huella,
                Auth::id() ?? 1
            );

            // Obtener información de la persona para la respuesta
            $persona = PerPersonas::find($request->persona_id);

            return response()->json([
                'success' => true,
                'message' => 'Huella registrada exitosamente',
                'data' => [
                    'huella_id' => $huella->id,
                    'persona' => $persona->nombreCompleto(),
                    'dedo' => $huella->nombre_dedo,
                    'fecha_registro' => $huella->feccaptura->format('d/m/Y H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error registrando huella', [
                'persona_id' => $request->persona_id,
                'dedo' => $request->dedo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verificar calidad de huella capturada
     */
    public function verificarCalidad(Request $request)
    {
        $request->validate([
            'quality' => 'required|numeric'
        ]);

        $calidadMinima = config('fingerprint.min_quality', 50);
        $calidad = $request->quality;

        $esAceptable = $calidad >= $calidadMinima;

        $mensaje = $esAceptable
            ? "Calidad aceptable ({$calidad}%)"
            : "Calidad insuficiente ({$calidad}%). Mínimo requerido: {$calidadMinima}%";

        return response()->json([
            'success' => true,
            'acceptable' => $esAceptable,
            'quality' => $calidad,
            'min_quality' => $calidadMinima,
            'message' => $mensaje
        ]);
    }

    /**
     * Obtener huellas registradas de una persona
     */
    public function huellasPersona(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:PER_PERSONAS,id'
        ]);

        try {
            $huellas = $this->huellaService->obtenerHuellasPersona($request->persona_id);

            return response()->json([
                'success' => true,
                'huellas' => $huellas->map(function($huella) {
                    return [
                        'id' => $huella->id,
                        'dedo' => $huella->dedo,
                        'nombre_dedo' => $huella->nombre_dedo,
                        'fecha_captura' => $huella->feccaptura->format('d/m/Y H:i:s')
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo huellas de persona', [
                'persona_id' => $request->persona_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las huellas registradas'
            ], 500);
        }
    }
}
