<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\Incapacidades\Api;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\Api\StoreEmpleadoIncapacidadApiRequest;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class EmpleadoIncapacidadApiController extends Controller
{
    private const SISTEMA_DEFAULT = 'AUTOGESTION';

    public function __construct(
        private readonly EmpleadoIncapacidadService $incapacidadService,
        private readonly EmpleadoIncapacidadDocumentoService $documentoService,
        private readonly NovedadNotificacionService $notificacionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documentoActorInput = trim((string) $request->query('documento_actor', ''));
        if ($documentoActorInput === '') {
            $documentoActorInput = trim((string) $request->query('documento_usuario', ''));
        }
        if ($documentoActorInput === '') {
            $documentoActorInput = trim((string) $request->query('identificacion', ''));
        }

        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = trim((string) ($seguridadActor['documento'] ?? ''));

        $documentoFiltro = trim((string) $request->query('identificacion', ''));
        if ($documentoFiltro === '') {
            $documentoFiltro = trim((string) $request->query('documento_usuario', ''));
        }
        if ($documentoFiltro === '') {
            $documentoFiltro = trim((string) $request->query('documento_persona', ''));
        }

        if ($documentoFiltro === '' && $documentoActor !== '') {
            $documentoFiltro = $documentoActor;
        }

        if ($documentoFiltro === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Debes enviar documento_persona (alias: identificacion, documento_usuario).',
            ], 422);
        }

        if ($documentoActor !== '' && $documentoFiltro !== $documentoActor) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes autorizacion para consultar incapacidades de otra persona.',
            ], 403);
        }

        $estado = strtoupper(trim((string) $request->query('estado', '')));
        if ($estado !== '' && ! isset(EmpleadoIncapacidadService::opcionesEstado()[$estado])) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro estado no es valido.',
            ], 422);
        }

        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        $incapacidades = $this->incapacidadService->obtenerIncapacidadesPaginadas([
            'persona_exacta' => $documentoFiltro,
            'estado' => $estado,
        ], $perPage);

        return response()->json([
            'ok' => true,
            'data' => $incapacidades->getCollection()->map(function ($incapacidad) {
                $persona = $this->incapacidadService->buscarPersona((string) ($incapacidad->documento_persona ?? $incapacidad->id_persona ?? ''));

                return [
                    'id_detalle' => (string) ($incapacidad->id_origen ?? ''),
                    'id_novedad' => (string) ($incapacidad->id_novedad ?? ''),
                    'documento_empleado' => (string) ($incapacidad->documento_persona ?? $incapacidad->id_persona ?? ''),
                    'nombre_empleado' => (string) ($persona['nombre'] ?? ''),
                    'causa_id' => (string) ($incapacidad->id_causa_incapacidad ?? ''),
                    'causa' => (string) ($incapacidad->causa ?? ''),
                    'diagnostico_id' => (string) ($incapacidad->id_diagnostico ?? ''),
                    'diagnostico_codigo' => (string) ($incapacidad->diagnostico_codigo ?? ''),
                    'diagnostico' => (string) ($incapacidad->diagnostico_descripcion ?? ''),
                    'eps_id' => (string) ($incapacidad->id_eps ?? ''),
                    'eps' => (string) ($incapacidad->eps_nombre ?? ''),
                    'arl_id' => (string) ($incapacidad->id_arl ?? ''),
                    'arl' => (string) ($incapacidad->arl_nombre ?? ''),
                    'tipo_incapacidad' => (string) ($incapacidad->tipo_incapacidad ?? ''),
                    'estado' => (string) ($incapacidad->estado ?? ''),
                    'fecha_inicio' => $this->formatearFecha($incapacidad->fecha_inicio ?? null),
                    'fecha_fin' => $this->formatearFecha($incapacidad->fecha_fin ?? null),
                    'dias_incapacidad' => $this->incapacidadService->calcularDiasIncapacidad(
                        $incapacidad->fecha_inicio ?? null,
                        $incapacidad->fecha_fin ?? null
                    ),
                    'fecha_registro' => $this->formatearFecha($incapacidad->fecha_creacion ?? null),
                    'hora_registro' => $this->formatearHora($incapacidad->fecha_creacion ?? null),
                    'adjuntos' => $this->documentoService->obtenerAdjuntosPorNovedad((string) ($incapacidad->id_novedad ?? ''))
                        ->map(function ($documento) {
                            return [
                                'id_documento' => (string) ($documento->id ?? ''),
                                'tipo_documento_id' => (string) ($documento->id_tipo_documento ?? ''),
                                'tipo_documento' => (string) ($documento->tipo_documento ?? ''),
                                'ruta_documento' => (string) ($documento->ruta_documento ?? ''),
                                'fecha_registro' => $this->formatearFecha($documento->fecha_creacion ?? null),
                            ];
                        })->values(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $incapacidades->currentPage(),
                'per_page' => $incapacidades->perPage(),
                'last_page' => $incapacidades->lastPage(),
                'total' => $incapacidades->total(),
                'count' => $incapacidades->count(),
                'has_more' => $incapacidades->hasMorePages(),
                'next_page_url' => $incapacidades->nextPageUrl(),
                'prev_page_url' => $incapacidades->previousPageUrl(),
            ],
        ]);
    }

    public function store(StoreEmpleadoIncapacidadApiRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? $payload['documento_usuario'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $nombreActor = $this->resolverNombreActor(
            isset($payload['nombre_actor']) ? trim((string) $payload['nombre_actor']) : (isset($payload['nombre_usuario']) ? trim((string) $payload['nombre_usuario']) : null),
            $documentoActor,
            $payload
        );
        $ipEquipoPayload = trim((string) ($payload['ip_equipo'] ?? ''));
        $ipEquipo = $ipEquipoPayload !== ''
            ? $ipEquipoPayload
            : trim((string) ($request->ip() ?? ''));
        $sistemaOrigen = trim((string) ($payload['sistema_origen'] ?? $request->header('X-System-Name', config('app.name', self::SISTEMA_DEFAULT))));

        $resultado = $this->incapacidadService->radicarIncapacidad(
            payload: $payload,
            adjuntos: $this->documentoService->extraerAdjuntosDesdeRequest($request),
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
                'message' => (string) ($resultado['message'] ?? 'No fue posible radicar la incapacidad.'),
            ], $httpStatus);
        }

        $this->notificacionService->enviarIncapacidadRadicadaRrhh(
            resultado: $resultado,
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            canal: 'API'
        );

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Incapacidad radicada correctamente.'),
            'data' => [
                'id_incapacidad' => (string) ($resultado['id_detalle'] ?? ''),
                'id_detalle' => (string) ($resultado['id_detalle'] ?? ''),
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'estado' => (string) ($resultado['estado'] ?? EmpleadoIncapacidadService::ESTADO_RADICADO),
                'adjuntos' => [
                    'guardados' => (int) data_get($resultado, 'adjuntos.guardados', 0),
                    'items' => $this->mapAdjuntosRespuesta(data_get($resultado, 'adjuntos.adjuntos', [])),
                ],
            ],
        ]);
    }

    public function radicar(StoreEmpleadoIncapacidadApiRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function schemaRadicar(Request $request): JsonResponse
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'multipart/form-data',
            'Authorization' => 'Bearer {jwt_token}',
        ];

        return response()->json([
            'ok' => true,
            'data' => [
                'metodo' => 'POST',
                'endpoint' => '/api/v2/empleados/incapacidades/radicar',
                'headers' => $headers,
                'scope_requerido' => 'empleados.incapacidades',
                'campos_requeridos' => [
                    'documento_actor' => 'string (quien radica)',
                    'documento_persona' => 'string',
                    'causa_id' => 'string',
                    'diagnostico_id' => 'string',
                    'eps_id' => 'string',
                    'arl_id' => 'string',
                    'tipo_incapacidad' => implode(', ', array_keys(EmpleadoIncapacidadService::opcionesTipoIncapacidad())),
                    'fecha_inicio' => 'Y-m-d',
                    'fecha_fin' => 'Y-m-d',
                ],
                'campos_opcionales' => [
                    'nombre_actor' => 'string',
                    'documento_radica' => 'string (alias de documento_actor)',
                    'documento_usuario' => 'string (alias de documento_actor)',
                    'identificacion' => 'string (alias de documento_persona)',
                    'nombre_usuario' => 'string (alias de nombre_actor)',
                    'observacion' => 'string|max:255',
                    'sistema_origen' => 'string',
                    'ip_equipo' => 'string',
                    'adjuntos[][tipo_documento_id]' => 'string',
                    'adjuntos[][archivo]' => 'file(pdf,jpg,jpeg,png|max:10MB)',
                ],
                'catalogos_endpoint' => URL::to('/api/v2/empleados/incapacidades/catalogos'),
            ],
        ]);
    }

    public function catalogos(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->incapacidadService->catalogos(),
        ]);
    }

    private function resolverNombreActor(?string $nombreActor, string $documentoActor, array $payload): ?string
    {
        $nombreActor = trim((string) $nombreActor);
        if ($nombreActor !== '') {
            return $nombreActor;
        }

        $documentos = collect([
            $documentoActor,
            (string) ($payload['documento_actor'] ?? ''),
            (string) ($payload['documento_usuario'] ?? ''),
            (string) ($payload['documento_persona'] ?? ''),
            (string) ($payload['identificacion'] ?? ''),
        ])
            ->map(fn ($doc) => trim((string) $doc))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($documentos as $documento) {
            $persona = $this->incapacidadService->buscarPersona($documento);
            $nombre = trim((string) ($persona['nombre'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return null;
    }

    private function formatearFecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($valor)->format('Y-m-d');
        } catch (\Throwable) {
            return trim((string) $valor) !== '' ? trim((string) $valor) : null;
        }
    }

    private function formatearHora(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($valor)->format('H:i:s');
        } catch (\Throwable) {
            return trim((string) $valor) !== '' ? trim((string) $valor) : null;
        }
    }

    private function mapAdjuntosRespuesta(mixed $adjuntos): array
    {
        if (! is_array($adjuntos)) {
            return [];
        }

        return collect($adjuntos)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return [
                    'id' => trim((string) ($item['id'] ?? '')),
                    'id_novedad' => trim((string) ($item['id_novedad'] ?? '')),
                    'id_tipo_documento' => trim((string) ($item['id_tipo_documento'] ?? '')),
                    'tipo_documento' => trim((string) ($item['tipo_documento'] ?? '')),
                    'ruta_documento' => trim((string) ($item['ruta_documento'] ?? '')),
                    'fuente' => trim((string) ($item['fuente'] ?? '')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolverDocumentoActorAsegurado(Request $request, string $documentoRequest): array
    {
        $documentoRequest = trim($documentoRequest);
        $claims = $request->attributes->get('api_jwt_claims', []);
        $claims = is_array($claims) ? $claims : [];

        $documentoToken = trim((string) ($claims['actor_documento'] ?? ''));
        if ($documentoToken === '') {
            return [
                'ok' => true,
                'documento' => $documentoRequest,
            ];
        }

        if ($documentoRequest !== '' && $documentoRequest !== $documentoToken) {
            return [
                'ok' => false,
                'status' => 403,
                'message' => 'El documento_actor no coincide con el actor del token.',
            ];
        }

        return [
            'ok' => true,
            'documento' => $documentoToken,
        ];
    }
}

