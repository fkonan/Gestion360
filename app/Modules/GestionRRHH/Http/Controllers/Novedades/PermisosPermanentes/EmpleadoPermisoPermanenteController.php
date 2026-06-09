<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\PermisosPermanentes;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\StoreEmpleadoPermisoPermanenteRequest;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService;
use Illuminate\Http\Request;

class EmpleadoPermisoPermanenteController extends Controller
{
    public function __construct(
        private readonly EmpleadoPermisoPermanenteService $permisoPermanenteService,
        private readonly EmpleadoPermisoPermanenteDocumentoService $documentoService,
        private readonly EmpleadoPermisoService $permisoService,
        private readonly NovedadNotificacionService $notificacionService
    ) {}

    public function create(Request $request)
    {
        $actor = $this->resolverContextoActor($request);

        return view('gestionrrhh::novedades.permisos-permanentes.radicar', [
            'documentoUsuarioDefault' => $actor['documento'],
            'opcionesJornada' => EmpleadoPermisoPermanenteService::opcionesJornada(),
        ]);
    }

    public function store(StoreEmpleadoPermisoPermanenteRequest $request)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();

        $resultado = $this->permisoPermanenteService->crearPermisoPermanente(
            payload: $payload,
            documentoActor: $actor['documento'],
            nombreActor: $actor['nombre'],
            origen: 'WEB',
            ipEquipo: (string) ($request->ip() ?? ''),
            sistemaOrigen: (string) config('app.name', 'AUTOGESTION')
        );

        if (! ($resultado['ok'] ?? false)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', (string) ($resultado['message'] ?? 'No fue posible radicar el permiso permanente.'));
        }

        $idNovedad = (string) ($resultado['id_novedad'] ?? '');
        $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
        $carta = $request->file('carta_solicitud');
        $soporte = $request->file('documento_soporte');

        if (! $carta || ! $soporte) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se encontraron todos los adjuntos requeridos para el permiso permanente.');
        }

        $resultadoAdjuntos = $this->documentoService->guardarDocumentos(
            idNovedad: $idNovedad,
            cartaSolicitud: $carta,
            documentoSoporte: $soporte,
            documentoPersona: $documentoPersona
        );

        if (! ($resultadoAdjuntos['ok'] ?? false)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', (string) ($resultadoAdjuntos['message'] ?? 'La solicitud quedo radicada pero no fue posible guardar los adjuntos.'));
        }

        $correoEnviado = $this->notificacionService->enviarPermisoPermanenteRadicado(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: (string) ($actor['documento'] ?? ''),
            nombreActor: isset($actor['nombre']) ? (string) $actor['nombre'] : null,
            canal: 'WEB'
        );

        $descripcion = $correoEnviado
            ? 'El permiso permanente fue radicado correctamente.'
            : 'El permiso permanente fue radicado correctamente. No fue posible enviar la notificacion por correo.';

        return redirect()
            ->route('gestionRRHH.permisos.permisos-permanentes.create')
            ->with('alert', [
                'type' => $correoEnviado ? 'success' : 'warning',
                'title' => 'Permiso permanente radicado',
                'description' => $descripcion,
            ]);
    }

    private function resolverContextoActor(Request $request): array
    {
        $usuario = $request->user();

        return [
            'documento' => $this->permisoService->obtenerDocumentoUsuario($usuario),
            'nombre' => $this->permisoService->obtenerNombreUsuario($usuario),
        ];
    }

}
