<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Http\Requests\AnularEmpleadoPermisoRequest;
use App\Modules\Administration\Http\Requests\RechazarEmpleadoPermisoRequest;
use App\Modules\Administration\Http\Requests\StoreEmpleadoPermisoRequest;
use App\Modules\Administration\Services\EmpleadoPermisoService;
use App\Modules\Administration\Services\EmpleadoPermisoPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmpleadoPermisoController extends Controller
{
    private const USUARIO_SIN_DOCUMENTO = '__SIN_DOCUMENTO__';

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly EmpleadoPermisoPdfService $permisoPdfService
    ) {}

    public function index()
    {
        return redirect()->route('empleados.permisos.create');
    }

    public function create()
    {
        return view('administration::empleados.permisos_radicar', [
            'opcionesMotivo' => EmpleadoPermisoService::opcionesMotivo(),
        ]);
    }

    public function misSolicitudes(Request $request)
    {
        $actor = $this->resolverContextoActor($request);

        $filtros = [
            'estado_flujo' => trim((string) $request->query('estado_flujo', '')),
            'creado_por_documento' => $actor['documento'] !== '' ? $actor['documento'] : self::USUARIO_SIN_DOCUMENTO,
        ];

        $permisos = $this->permisoService->obtenerPermisosPaginados($filtros, 20);

        $permisos->getCollection()->transform(function ($permiso) {
            $estadoFlujo = (string) ($permiso->estado_flujo ?? '');
            $permiso->puede_anular = $estadoFlujo !== EmpleadoPermisoService::ESTADO_ANULADO;

            return $permiso;
        });

        return view('administration::empleados.permisos', [
            'permisos' => $permisos,
            'filtros' => $filtros,
            'opcionesEstadoFlujo' => EmpleadoPermisoService::opcionesEstadoFlujo(),
        ]);
    }

    public function store(StoreEmpleadoPermisoRequest $request)
    {
        $actor = $this->resolverContextoActor($request);

        $resultado = $this->permisoService->crearPermiso(
            payload: $request->validated(),
            documentoActor: $actor['documento'],
            nombreActor: $actor['nombre'],
            origen: 'WEB',
            ipEquipo: (string) ($request->ip() ?? ''),
            sistemaOrigen: (string) config('app.name', 'AUTOGESTION')
        );

        if (! ($resultado['ok'] ?? false)) {
            $message = (string) ($resultado['message'] ?? 'No fue posible radicar el permiso.');

            return redirect()->back()->withInput()->with('error', $message);
        }

        return redirect()
            ->route('empleados.permisos.create')
            ->with('success', 'Permiso radicado correctamente.');
    }

    public function aprobarJefe(Request $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->permisoService->aprobarPorJefe(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible aprobar por jefe.', 'Permiso aprobado por jefe.');
    }

    public function aprobarRrhh(Request $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $resultado = $this->permisoService->aprobarPorRrhh(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            esRrhh: $actor['es_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible aprobar por RRHH.', 'Permiso aprobado por RRHH.');
    }

    public function rechazar(RechazarEmpleadoPermisoRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();

        $resultado = $this->permisoService->rechazarPermiso(
            idNovedad: $idNovedad,
            nivel: (string) $payload['nivel'],
            motivoRechazo: (string) $payload['motivo_rechazo'],
            documentoActor: $actor['documento'],
            esRrhh: $actor['es_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible rechazar el permiso.', 'Permiso rechazado correctamente.');
    }

    public function anular(AnularEmpleadoPermisoRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();
        $resultado = $this->permisoService->anularPermiso(
            idNovedad: $idNovedad,
            motivoAnulacion: (string) $payload['motivo_anulacion'],
            documentoActor: $actor['documento'],
            esRrhh: $actor['es_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible anular el permiso.', 'Permiso anulado correctamente.');
    }

    public function pdf(string $idNovedad)
    {
        try {
            $detalle = $this->permisoService->obtenerDetalleParaPdf($idNovedad);
            $pdf = $this->permisoPdfService->generarDesdeDetalle($detalle);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="permiso-empleado-'.$idNovedad.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error generando PDF de permiso empleado', [
                'id_novedad' => $idNovedad,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'No fue posible generar el PDF del permiso.');
        }
    }

    public function persona(string $documento)
    {
        $persona = $this->permisoService->buscarPersona($documento);
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

    private function resolverContextoActor(Request $request): array
    {
        $usuario = $request->user();

        return [
            'documento' => $this->permisoService->obtenerDocumentoUsuario($usuario),
            'nombre' => $this->permisoService->obtenerNombreUsuario($usuario),
            'es_rrhh' => $this->permisoService->esUsuarioRrhh($usuario),
            'es_super_admin' => $this->permisoService->esUsuarioSuperAdmin($usuario),
        ];
    }

    private function redirigirConResultado(array $resultado, string $errorFallback, string $successFallback)
    {
        if (! ($resultado['ok'] ?? false)) {
            return redirect()->back()->with('error', (string) ($resultado['message'] ?? $errorFallback));
        }

        return redirect()->back()->with('success', (string) ($resultado['message'] ?? $successFallback));
    }
}
