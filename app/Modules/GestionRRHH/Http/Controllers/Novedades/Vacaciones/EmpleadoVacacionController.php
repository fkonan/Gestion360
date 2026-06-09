<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\Vacaciones;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\StoreEmpleadoVacacionRequest;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionService;
use Illuminate\Http\Request;

class EmpleadoVacacionController extends Controller
{
    public function __construct(
        private readonly EmpleadoVacacionService $vacacionService,
        private readonly EmpleadoVacacionDocumentoService $documentoService,
        private readonly EmpleadoPermisoService $permisoService,
        private readonly NovedadNotificacionService $notificacionService
    ) {}

    public function create(Request $request)
    {
        $actor = $this->resolverContextoActor($request);

        return view('gestionrrhh::novedades.vacaciones.radicar', [
            'documentoUsuarioDefault' => $actor['documento'],
        ]);
    }

    public function store(StoreEmpleadoVacacionRequest $request)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();

        $resultado = $this->vacacionService->crearVacacion(
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
                ->with('error', (string) ($resultado['message'] ?? 'No fue posible radicar la solicitud de vacaciones.'));
        }

        $idNovedad = (string) ($resultado['id_novedad'] ?? '');
        $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
        $carta = $request->file('carta');

        if (! $carta) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se encontro el archivo de carta para asociarlo a la solicitud.');
        }

        $resultadoCarta = $this->documentoService->guardarCarta(
            idNovedad: $idNovedad,
            carta: $carta,
            documentoPersona: $documentoPersona
        );

        if (! ($resultadoCarta['ok'] ?? false)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', (string) ($resultadoCarta['message'] ?? 'La solicitud quedo radicada pero no fue posible guardar la carta.'));
        }

        $correoEnviado = $this->notificacionService->enviarVacacionRadicada(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: (string) ($actor['documento'] ?? ''),
            nombreActor: isset($actor['nombre']) ? (string) $actor['nombre'] : null,
            canal: 'WEB'
        );

        $descripcion = $correoEnviado
            ? 'La solicitud de vacaciones fue radicada correctamente.'
            : 'La solicitud de vacaciones fue radicada correctamente. No fue posible enviar la notificacion por correo.';

        return redirect()
            ->route('gestionRRHH.permisos.vacaciones.create')
            ->with('alert', [
                'type' => $correoEnviado ? 'success' : 'warning',
                'title' => 'Vacaciones radicadas',
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

