<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\Descargos;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\Descargos\StoreDescargoCitacionRequest;
use App\Modules\GestionRRHH\Services\Novedades\Descargos\DescargoCitacionService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DescargoCitacionController extends Controller
{
    public function __construct(
        private readonly DescargoCitacionService $citacionService,
        private readonly EmpleadoPermisoService $permisoService,
        private readonly NovedadNotificacionService $notificacionService
    ) {}

    public function create(Request $request)
    {
        $actor = $this->resolverContextoActor($request);

        return view('gestionrrhh::novedades.descargos.citar', [
            'documentoUsuarioDefault' => $actor['documento'],
        ]);
    }

    public function store(StoreDescargoCitacionRequest $request)
    {
        $payload = $request->validated();
        $actor = $this->resolverContextoActor($request);

        $documentoPersona = trim((string) ($payload['documento_persona'] ?? ''));
        $persona = $this->permisoService->buscarPersona($documentoPersona);
        if (! $persona) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No se encontro una persona activa con el documento indicado.');
        }

        $resultado = $this->citacionService->crearCitacion(
            payload: $payload,
            documentoActor: $actor['documento'],
            origen: 'WEB',
            ipEquipo: (string) ($request->ip() ?? ''),
            sistemaOrigen: (string) config('app.name', 'AUTOGESTION')
        );

        if (! ($resultado['ok'] ?? false)) {
            if (! empty($resultado['error'])) {
                Log::error('Error creando citacion a descargos', [
                    'documento_persona' => $documentoPersona,
                    'documento_actor' => $actor['documento'],
                    'message' => (string) $resultado['error'],
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', (string) ($resultado['message'] ?? 'No fue posible registrar la citacion a descargos.'));
        }

        $correoEnviado = $this->notificacionService->enviarCitacionDescargosEmpleado(
            idCitacion: (string) ($resultado['id'] ?? ''),
            payload: $payload,
            persona: $persona,
            actor: $actor,
            canal: 'WEB'
        );

        $descripcion = $correoEnviado
            ? 'La citacion a descargos fue registrada y notificada al empleado.'
            : 'La citacion a descargos fue registrada. No fue posible enviar el correo de notificacion al empleado.';

        return redirect()
            ->route('gestionRRHH.descargos.citaciones.create')
            ->with('alert', [
                'type' => $correoEnviado ? 'success' : 'warning',
                'title' => 'Citacion registrada',
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

