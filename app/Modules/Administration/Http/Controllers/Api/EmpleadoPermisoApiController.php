<?php

namespace App\Modules\Administration\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Http\Requests\Api\StoreEmpleadoPermisoApiRequest;
use App\Modules\Administration\Services\EmpleadoPermisoService;
use Illuminate\Http\JsonResponse;

class EmpleadoPermisoApiController extends Controller
{
    private const SISTEMA_DEFAULT = 'AUTOGESTION';

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService
    ) {}

    public function store(StoreEmpleadoPermisoApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $documentoActor = trim((string) ($payload['documento_usuario'] ?? ''));
        $nombreActor = isset($payload['nombre_usuario']) ? trim((string) $payload['nombre_usuario']) : null;
        $ipEquipo = trim((string) ($request->ip() ?? ($payload['ip_equipo'] ?? '')));
        $sistemaOrigen = trim((string) ($payload['sistema_origen'] ?? $request->header('X-System-Name', config('app.name', self::SISTEMA_DEFAULT))));

        $resultado = $this->permisoService->crearPermiso(
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            origen: 'API',
            ipEquipo: $ipEquipo,
            sistemaOrigen: $sistemaOrigen
        );

        if (! ($resultado['ok'] ?? false)) {
            $httpStatus = (int) ($resultado['http_status'] ?? 422);

            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible radicar el permiso.'),
            ], $httpStatus);
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Permiso radicado correctamente.'),
            'data' => [
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_RADICADO),
            ],
        ]);
    }
}
