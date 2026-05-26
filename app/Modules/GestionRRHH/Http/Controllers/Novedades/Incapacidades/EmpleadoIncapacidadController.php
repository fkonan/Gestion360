<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\Incapacidades;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\RechazarEmpleadoIncapacidadRequest;
use App\Modules\GestionRRHH\Http\Requests\StoreEmpleadoIncapacidadSeguimientoRequest;
use App\Modules\GestionRRHH\Http\Requests\StoreEmpleadoIncapacidadRequest;
use App\Modules\GestionRRHH\Http\Requests\UpdateEmpleadoIncapacidadRequest;
use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpleadoIncapacidadController extends Controller
{
    public function __construct(
        private readonly EmpleadoIncapacidadService $incapacidadService,
        private readonly EmpleadoIncapacidadDocumentoService $documentoService,
        private readonly NovedadNotificacionService $notificacionService
    ) {}

    public function create(Request $request)
    {
        $actor = $this->resolverContextoActor($request);
        $diagnosticos = $this->incapacidadService->obtenerDiagnosticosIniciales();
        $diagnosticoSeleccionadoId = trim((string) $request->old('diagnostico_id', ''));

        if ($diagnosticoSeleccionadoId !== '' && ! isset($diagnosticos[$diagnosticoSeleccionadoId])) {
            $diagnosticoSeleccionado = $this->incapacidadService->obtenerDiagnosticoPorId($diagnosticoSeleccionadoId);
            if ($diagnosticoSeleccionado) {
                $diagnosticos[$diagnosticoSeleccionado['id']] = $diagnosticoSeleccionado['text'];
            }
        }

        return view('gestionrrhh::novedades.incapacidades.radicar', [
            'causas' => $this->incapacidadService->obtenerCausas(),
            'diagnosticos' => $diagnosticos,
            'eps' => $this->incapacidadService->obtenerEps(),
            'arl' => $this->incapacidadService->obtenerArl(),
            'tiposIncapacidad' => EmpleadoIncapacidadService::opcionesTipoIncapacidad(),
            'tiposDocumento' => $this->documentoService->obtenerTiposDocumento(),
            'tiposDocumentoPorCausa' => $this->incapacidadService->obtenerDocumentosPorCausa(),
            'documentoUsuarioDefault' => $actor['documento'],
        ]);
    }

    public function store(StoreEmpleadoIncapacidadRequest $request)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();
        $adjuntos = $this->documentoService->extraerAdjuntosDesdeRequest($request);

        $resultado = $this->incapacidadService->radicarIncapacidad(
            payload: $payload,
            adjuntos: $adjuntos,
            documentoActor: $actor['documento'],
            nombreActor: $actor['nombre'],
            origen: 'WEB',
            ipEquipo: (string) ($request->ip() ?? ''),
            sistemaOrigen: (string) config('app.name', 'AUTOGESTION')
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible radicar la incapacidad.');
            if (! empty($resultado['error'])) {
                $message .= ' '.(string) $resultado['error'];
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        $adjuntosGuardados = (int) data_get($resultado, 'adjuntos.guardados', 0);
        $correoEnviadoRrhh = $this->notificacionService->enviarIncapacidadRadicadaRrhh(
            resultado: $resultado,
            payload: $payload,
            documentoActor: (string) ($actor['documento'] ?? ''),
            nombreActor: isset($actor['nombre']) ? (string) $actor['nombre'] : null,
            canal: 'WEB'
        );
        $descripcionAlerta = 'La incapacidad quedo radicada correctamente.';
        $descripcionAlerta .= '<br>Radicado: <strong>'.e((string) ($resultado['id_detalle'] ?? $resultado['id_incapacidad'] ?? '')).'</strong>.';
        $descripcionAlerta .= '<br>Adjuntos guardados: '.$adjuntosGuardados.'.';
        $descripcionAlerta .= $correoEnviadoRrhh
            ? '<br>Se envio notificacion a RRHH.'
            : '<br>No fue posible enviar la notificacion a RRHH.';

        return redirect()
            ->route('gestionRRHH.permisos.incapacidades.create')
            ->with('alert', [
                'type' => 'success',
                'title' => 'Incapacidad radicada correctamente',
                'description' => $descripcionAlerta,
            ]);
    }

    public function gestion(Request $request, string $idNovedad)
    {
        $detalle = $this->incapacidadService->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return redirect()->route('gestionRRHH.permisos.rrhh')->with('error', 'No se encontro la solicitud de incapacidad.');
        }

        $diagnosticos = $this->incapacidadService->obtenerDiagnosticosIniciales(20);
        $diagnosticoActualId = trim((string) ($detalle->id_diagnostico ?? ''));
        if ($diagnosticoActualId !== '' && ! isset($diagnosticos[$diagnosticoActualId])) {
            $diagnosticoActual = $this->incapacidadService->obtenerDiagnosticoPorId($diagnosticoActualId);
            if ($diagnosticoActual) {
                $diagnosticos[$diagnosticoActual['id']] = $diagnosticoActual['text'];
            }
        }

        return view('gestionrrhh::novedades.incapacidades.gestion', [
            'novedad' => $detalle,
            'causas' => $this->incapacidadService->obtenerCausas(),
            'diagnosticos' => $diagnosticos,
            'eps' => $this->incapacidadService->obtenerEps(),
            'arl' => $this->incapacidadService->obtenerArl(),
            'tiposIncapacidad' => EmpleadoIncapacidadService::opcionesTipoIncapacidad(),
            'adjuntos' => $this->documentoService->obtenerAdjuntosPorNovedad($idNovedad),
            'seguimientos' => $this->incapacidadService->obtenerSeguimientos($idNovedad),
            'puedeRegistrarSeguimiento' => strtoupper(trim((string) ($detalle->estado ?? ''))) === EmpleadoIncapacidadService::ESTADO_APROBADO,
        ]);
    }

    public function actualizarGestion(UpdateEmpleadoIncapacidadRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->incapacidadService->actualizarSolicitud(
            idNovedad: $idNovedad,
            payload: $request->validated(),
            documentoActor: $actor['documento']
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible actualizar la solicitud de incapacidad.');
            if (! empty($resultado['error'])) {
                $message .= ' '.(string) $resultado['error'];
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        return redirect()->route('gestionRRHH.permisos.incapacidades.gestion', ['idNovedad' => $idNovedad])
            ->with('success', (string) ($resultado['message'] ?? 'La solicitud de incapacidad fue actualizada.'));
    }

    public function aprobar(Request $request, string $idNovedad, BloqueoService $bloqueoService)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->incapacidadService->aprobarSolicitud(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            bloqueoService: $bloqueoService
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible aprobar la solicitud de incapacidad.');
            if (! empty($resultado['error'])) {
                $message .= ' '.(string) $resultado['error'];
            }

            return redirect()->back()->with('error', $message);
        }

        return redirect()->route('gestionRRHH.permisos.rrhh')
            ->with('success', (string) ($resultado['message'] ?? 'La incapacidad fue aprobada correctamente.'));
    }

    public function rechazar(RechazarEmpleadoIncapacidadRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->incapacidadService->rechazarSolicitud(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            motivo: (string) $request->validated()['motivo_rechazo']
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible rechazar la solicitud de incapacidad.');
            if (! empty($resultado['error'])) {
                $message .= ' '.(string) $resultado['error'];
            }

            return redirect()->back()->with('error', $message);
        }

        return redirect()->route('gestionRRHH.permisos.rrhh')
            ->with('success', (string) ($resultado['message'] ?? 'La incapacidad fue rechazada correctamente.'));
    }

    public function registrarSeguimiento(StoreEmpleadoIncapacidadSeguimientoRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->incapacidadService->agregarSeguimiento(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            observacion: (string) $request->validated()['observacion_seguimiento']
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible registrar el seguimiento.');
            if (! empty($resultado['error'])) {
                $message .= ' '.(string) $resultado['error'];
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        return redirect()->route('gestionRRHH.permisos.incapacidades.gestion', ['idNovedad' => $idNovedad])
            ->with('success', (string) ($resultado['message'] ?? 'Seguimiento registrado correctamente.'));
    }

    public function persona(string $documento): JsonResponse
    {
        $persona = $this->incapacidadService->buscarPersona($documento);
        if (! $persona) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la persona activa.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $persona,
        ]);
    }

    public function diagnosticos(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'results' => $this->incapacidadService->buscarDiagnosticos(
                termino: (string) $request->query('q', ''),
                limite: (int) $request->query('limit', 10)
            ),
        ]);
    }

    private function resolverContextoActor(Request $request): array
    {
        $usuario = $request->user();
        $documento = $usuario?->persona?->PerNumDoc
            ?? $usuario?->IdUsuario
            ?? '';
        $nombre = null;

        if ($usuario?->persona && method_exists($usuario->persona, 'nombreCompleto')) {
            $nombre = $usuario->persona->nombreCompleto();
        }

        return [
            'documento' => trim((string) $documento),
            'nombre' => $nombre !== null ? trim((string) $nombre) : null,
        ];
    }

}

