<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades\Api;

use App\Constants\Permisos;
use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Http\Requests\Api\StoreEmpleadoVacacionApiRequest;
use App\Modules\GestionRRHH\Http\Requests\Api\StoreEmpleadoPermisoPermanenteApiRequest;
use App\Modules\GestionRRHH\Http\Requests\Api\StoreEmpleadoPermisoApiRequest;
use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\Novedades\ActorNovedadService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use App\Modules\GestionRRHH\Services\Novedades\EmpleadoNovedadService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadTipoResolver;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class EmpleadoSolicitudApiController extends Controller
{
    private const ESTADOS_CONSULTA_JEFE = [
        EmpleadoPermisoService::ESTADO_RADICADO,
        EmpleadoPermisoService::ESTADO_JEFE_APROBADO,
        EmpleadoPermisoService::ESTADO_RECHAZADO,
        EmpleadoPermisoService::ESTADO_ANULADO,
    ];
    private const ESTADOS_CONSULTA_RRHH = [
        EmpleadoPermisoService::ESTADO_JEFE_APROBADO,
        EmpleadoPermisoService::ESTADO_RADICADO,
    ];
    private const ESTADOS_FLUJO_VALIDOS = [
        EmpleadoPermisoService::ESTADO_RADICADO,
        EmpleadoPermisoService::ESTADO_JEFE_APROBADO,
        EmpleadoPermisoService::ESTADO_APROBADO,
        EmpleadoPermisoService::ESTADO_RECHAZADO,
        EmpleadoPermisoService::ESTADO_ANULADO,
    ];

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly EmpleadoIncapacidadService $incapacidadService,
        private readonly EmpleadoVacacionService $vacacionService,
        private readonly EmpleadoPermisoPermanenteService $permisoPermanenteService,
        private readonly EmpleadoNovedadService $novedadService,
        private readonly ActorNovedadService $actorNovedadService,
        private readonly NovedadNotificacionService $notificacionService,
        private readonly EmpleadoPermisoDocumentoService $documentoService,
        private readonly EmpleadoVacacionDocumentoService $vacacionDocumentoService,
        private readonly EmpleadoPermisoPermanenteDocumentoService $permisoPermanenteDocumentoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documentoUsuarioInput = $this->resolverDocumentoRequest($request, [
            'documento_actor',
            'documento_usuario',
            'documento_persona',
            'identificacion',
            'documento',
        ], true);

        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoUsuarioInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoUsuario = (string) ($seguridadActor['documento'] ?? '');

        if ($documentoUsuario === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Debes enviar documento_actor (alias: documento_usuario, documento_persona, identificacion).',
            ], 422);
        }

        $estadoFlujo = strtoupper(trim((string) $request->query('estado_flujo', '')));
        $estadosValidos = array_keys(EmpleadoPermisoService::opcionesEstadoFlujo());
        if ($estadoFlujo !== '' && ! in_array($estadoFlujo, $estadosValidos, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro estado_flujo no es valido.',
            ], 422);
        }

        $permisos = $this->permisoService->obtenerPermisosPaginados([
            'persona_exacta' => $documentoUsuario,
            'estado_flujo' => $estadoFlujo,
        ], $this->resolverPerPage($request));

        return response()->json([
            'ok' => true,
            'data' => $permisos->getCollection()->map(fn ($permiso) => $this->mapPermisoParaApi($permiso))->values(),
            'meta' => $this->metaPaginador($permisos),
        ]);
    }

    public function equipoJefe(Request $request): JsonResponse
    {
        $documentoJefeInput = $this->resolverDocumentoRequest($request, [
            'documento_actor',
            'documento_usuario',
            'identificacion',
            'documento',
        ], true);

        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoJefeInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoJefe = (string) ($seguridadActor['documento'] ?? '');

        if ($documentoJefe === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Debes enviar documento_actor (alias: documento_usuario, identificacion).',
            ], 422);
        }

        $estadoFlujo = strtoupper(trim((string) $request->query('estado_flujo', EmpleadoPermisoService::ESTADO_RADICADO)));
        if ($estadoFlujo !== '' && ! in_array($estadoFlujo, self::ESTADOS_CONSULTA_JEFE, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro estado_flujo no es valido para bandeja de jefe.',
            ], 422);
        }

        $filtros = [
            'estado' => $estadoFlujo,
            'solo_no_aprobadas' => true,
        ];

        $novedades = $this->novedadService->obtenerNovedadesEquipoPaginadas(
            documentoJefe: $documentoJefe,
            filtros: $filtros,
            perPage: $this->resolverPerPage($request)
        );

        return $this->responderListadoNovedades($novedades);
    }

    public function pendientesRrhh(Request $request): JsonResponse
    {
        $documentoActorInput = $this->resolverDocumentoRequest($request, [
            'documento_actor',
            'documento_usuario',
            'identificacion',
            'documento',
        ], true);

        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');

        if ($documentoActor === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Debes enviar documento_actor (alias: documento_usuario, identificacion).',
            ], 422);
        }

        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $puedeGestionarRrhh = (bool) $permisosActor['puede_gestionar_permisos_rrhh']
            || $this->requestTieneScope($request, 'empleados.permisos.rrhh');
        if (! $puedeGestionarRrhh && ! $permisosActor['es_super_admin']) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes autorizacion de RRHH para consultar permisos pendientes.',
            ], 403);
        }

        $estadoFlujo = strtoupper(trim((string) $request->query('estado_flujo', '')));
        if ($estadoFlujo !== '' && ! in_array($estadoFlujo, self::ESTADOS_CONSULTA_RRHH, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro estado_flujo no es valido para bandeja RRHH.',
            ], 422);
        }

        $filtros = [
            'estado' => $estadoFlujo,
            'solo_no_aprobadas' => true,
            'solo_gestionables_rrhh' => true,
        ];
        $novedades = $this->novedadService->obtenerNovedadesPaginadas(
            filtros: $filtros,
            perPage: $this->resolverPerPage($request)
        );

        return $this->responderListadoNovedades($novedades);
    }

    public function store(StoreEmpleadoPermisoApiRequest $request): JsonResponse
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
        $sistemaOrigen = 'Appmovil';

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

        $adjuntos = $this->documentoService->extraerAdjuntosDesdeRequest($request);

        $resultadoAdjuntos = $this->documentoService->guardarAdjuntos(
            idNovedad: (string) ($resultado['id_novedad'] ?? ''),
            adjuntos: $adjuntos
        );

        $message = (string) ($resultado['message'] ?? 'Permiso radicado correctamente.');
        if (! ($resultadoAdjuntos['ok'] ?? true)) {
            $message .= ' El permiso fue radicado, pero no fue posible guardar adjuntos.';
        }

        $persona = $this->permisoService->buscarPersona((string) ($payload['identificacion'] ?? ''));
        $actor = [
            'documento' => $documentoActor,
            'nombre' => $nombreActor,
        ];
        $jefeDirecto = is_array($resultado['jefe_directo'] ?? null)
            ? $resultado['jefe_directo']
            : null;
        $correoEnviado = $this->notificacionService->enviarPermisoRadicado(
            resultado: $resultado,
            payload: $payload,
            persona: $persona,
            actor: $actor,
            jefeDirecto: $jefeDirecto,
            adjuntosCorreo: $this->documentoService->construirAdjuntosCorreo(
                $adjuntos,
                (string) ($payload['identificacion'] ?? '')
            ),
            canal: 'API'
        );
        if (! $correoEnviado) {
            $message .= ' No fue posible enviar la notificacion por correo.';
        }

        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => [
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_RADICADO),
                'radicado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => (string) $nombreActor,
                ],
                'adjuntos' => [
                    'ok' => (bool) ($resultadoAdjuntos['ok'] ?? true),
                    'guardados' => (int) ($resultadoAdjuntos['guardados'] ?? 0),
                ],
                'jefe_directo' => is_array($resultado['jefe_directo'] ?? null)
                    ? [
                        'identificacion' => (string) ($resultado['jefe_directo']['identificacion'] ?? ''),
                        'nombre' => (string) ($resultado['jefe_directo']['nombre'] ?? ''),
                        'correo' => (string) ($resultado['jefe_directo']['correo'] ?? ''),
                    ]
                    : null,
                'notificacion_email_enviada' => $correoEnviado,
            ],
        ]);
    }

    public function radicar(StoreEmpleadoPermisoApiRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function storeVacaciones(StoreEmpleadoVacacionApiRequest $request): JsonResponse
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
        $sistemaOrigen = 'Appmovil';

        $resultado = $this->vacacionService->crearVacacion(
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            origen: 'API',
            ipEquipo: $ipEquipo,
            sistemaOrigen: $sistemaOrigen
        );

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible radicar la solicitud de vacaciones.'),
            ], (int) ($resultado['http_status'] ?? 422));
        }

        $idNovedad = (string) ($resultado['id_novedad'] ?? '');
        $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
        $carta = $request->file('carta');

        if (! $carta) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro el archivo de carta para asociarlo a la solicitud.',
            ], 422);
        }

        $resultadoCarta = $this->vacacionDocumentoService->guardarCarta(
            idNovedad: $idNovedad,
            carta: $carta,
            documentoPersona: $documentoPersona
        );

        if (! ($resultadoCarta['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultadoCarta['message'] ?? 'La solicitud quedo radicada pero no fue posible guardar la carta.'),
            ], (int) ($resultadoCarta['http_status'] ?? 500));
        }

        $correoEnviado = $this->enviarCorreoNotificacionVacacionesRadicadas(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor
        );

        return response()->json([
            'ok' => true,
            'message' => $correoEnviado
                ? 'Vacaciones radicadas correctamente.'
                : 'Vacaciones radicadas correctamente. No fue posible enviar la notificacion por correo.',
            'data' => [
                'id_novedad' => $idNovedad,
                'id_detalle' => (string) ($resultado['id_detalle'] ?? ''),
                'estado' => (string) ($resultado['estado'] ?? EmpleadoVacacionService::ESTADO_RADICADO),
                'radicado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => (string) $nombreActor,
                ],
                'requiere_aprobacion_rrhh' => (bool) ($resultado['requiere_aprobacion_rrhh'] ?? true),
                'aprobador' => is_array($resultado['aprobador'] ?? null)
                    ? [
                        'tipo' => (string) ($resultado['aprobador']['tipo'] ?? ''),
                        'documento' => (string) ($resultado['aprobador']['documento'] ?? ''),
                        'nombre' => (string) ($resultado['aprobador']['nombre'] ?? ''),
                        'correo' => (string) ($resultado['aprobador']['correo'] ?? ''),
                    ]
                    : null,
                'carta' => [
                    'ok' => true,
                    'ruta_documento' => (string) data_get($resultadoCarta, 'adjunto.ruta_documento', ''),
                    'tipo_documento_id' => (string) data_get($resultadoCarta, 'adjunto.id_tipo_documento', ''),
                ],
                'notificacion_email_enviada' => $correoEnviado,
            ],
        ]);
    }

    public function radicarVacaciones(StoreEmpleadoVacacionApiRequest $request): JsonResponse
    {
        return $this->storeVacaciones($request);
    }

    public function storePermisoPermanente(StoreEmpleadoPermisoPermanenteApiRequest $request): JsonResponse
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
        $sistemaOrigen = 'Appmovil';

        $resultado = $this->permisoPermanenteService->crearPermisoPermanente(
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            origen: 'API',
            ipEquipo: $ipEquipo,
            sistemaOrigen: $sistemaOrigen
        );

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible radicar el permiso permanente.'),
            ], (int) ($resultado['http_status'] ?? 422));
        }

        $idNovedad = (string) ($resultado['id_novedad'] ?? '');
        $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
        $cartaSolicitud = $request->file('carta_solicitud');
        $documentoSoporte = $request->file('documento_soporte');

        if (! $cartaSolicitud || ! $documentoSoporte) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontraron todos los archivos requeridos para el permiso permanente.',
            ], 422);
        }

        $resultadoAdjuntos = $this->permisoPermanenteDocumentoService->guardarDocumentos(
            idNovedad: $idNovedad,
            cartaSolicitud: $cartaSolicitud,
            documentoSoporte: $documentoSoporte,
            documentoPersona: $documentoPersona
        );

        if (! ($resultadoAdjuntos['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultadoAdjuntos['message'] ?? 'La solicitud quedo radicada pero no fue posible guardar los adjuntos.'),
            ], (int) ($resultadoAdjuntos['http_status'] ?? 500));
        }

        $correoEnviado = $this->enviarCorreoNotificacionPermisoPermanenteRadicado(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor
        );

        return response()->json([
            'ok' => true,
            'message' => $correoEnviado
                ? 'Permiso permanente radicado correctamente.'
                : 'Permiso permanente radicado correctamente. No fue posible enviar la notificacion por correo.',
            'data' => [
                'id_novedad' => $idNovedad,
                'id_detalle' => (string) ($resultado['id_detalle'] ?? ''),
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoPermanenteService::ESTADO_RADICADO),
                'radicado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => (string) $nombreActor,
                ],
                'jefe_directo' => is_array($resultado['jefe_directo'] ?? null)
                    ? [
                        'identificacion' => (string) ($resultado['jefe_directo']['identificacion'] ?? ''),
                        'nombre' => (string) ($resultado['jefe_directo']['nombre'] ?? ''),
                        'correo' => (string) ($resultado['jefe_directo']['correo'] ?? ''),
                    ]
                    : null,
                'adjuntos' => [
                    'ok' => true,
                    'guardados' => (int) ($resultadoAdjuntos['guardados'] ?? 0),
                ],
                'notificacion_email_enviada' => $correoEnviado,
            ],
        ]);
    }

    public function radicarPermisoPermanente(StoreEmpleadoPermisoPermanenteApiRequest $request): JsonResponse
    {
        return $this->storePermisoPermanente($request);
    }

    public function novedades(Request $request): JsonResponse
    {
        $input = $request->query();
        if (trim((string) ($input['documento_actor'] ?? '')) === '') {
            $input['documento_actor'] = trim((string) ($input['documento_usuario'] ?? $input['identificacion'] ?? ''));
        }
        if (trim((string) ($input['documento_actor'] ?? '')) === '') {
            $input['documento_actor'] = $this->obtenerDocumentoActorToken($request);
        }
        if (trim((string) ($input['documento_persona'] ?? '')) === '') {
            $input['documento_persona'] = trim((string) ($input['identificacion_persona'] ?? $input['identificacion'] ?? ''));
        }
        $input['documento_usuario'] = trim((string) ($input['documento_usuario'] ?? $input['documento_actor'] ?? ''));
        $input['identificacion'] = trim((string) ($input['identificacion'] ?? $input['documento_persona'] ?? ''));
        $input['nombre_usuario'] = trim((string) ($input['nombre_usuario'] ?? $input['nombre_actor'] ?? ''));

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'documento_persona' => 'nullable|string|max:50',
            'tipo' => 'nullable|string|max:40',
            'estado' => 'nullable|string|max:40',
            'solo_aprobadas' => 'nullable|in:0,1',
            'solo_no_aprobadas' => 'nullable|in:0,1',
            'solo_hoy' => 'nullable|in:0,1',
            'incluir_radicadas_por' => 'nullable|in:0,1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $documentoPersona = trim((string) ($payload['documento_persona'] ?? ''));
        if ($documentoPersona === '') {
            $documentoPersona = $documentoActor;
        }
        $tipo = strtoupper(trim((string) ($payload['tipo'] ?? '')));
        $estado = strtoupper(trim((string) ($payload['estado'] ?? '')));
        $soloAprobadas = (string) ($payload['solo_aprobadas'] ?? '0') === '1';
        $soloNoAprobadas = (string) ($payload['solo_no_aprobadas'] ?? '0') === '1';
        $soloHoy = (string) ($payload['solo_hoy'] ?? '0') === '1';
        $incluirRadicadasPor = (string) ($payload['incluir_radicadas_por'] ?? '0') === '1';

        if ($soloAprobadas && $soloNoAprobadas) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes combinar solo_aprobadas y solo_no_aprobadas al mismo tiempo.',
            ], 422);
        }

        if ($tipo !== '' && ! in_array($tipo, ['PERMISO', 'PERMISO_PERMANENTE', 'INCAPACIDAD', 'VACACION'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro tipo no es valido.',
            ], 422);
        }

        if ($estado !== '' && ! in_array($estado, self::ESTADOS_FLUJO_VALIDOS, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El parametro estado no es valido.',
            ], 422);
        }

        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $puedeVerTerceros = $permisosActor['puede_gestionar_permisos_rrhh'] || $permisosActor['es_super_admin'];
        if ($documentoPersona !== $documentoActor && ! $puedeVerTerceros) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes autorizacion para consultar novedades de otra persona.',
            ], 403);
        }

        $filtros = [
            'tipo' => $tipo,
            'estado' => $estado,
            'solo_aprobadas' => $soloAprobadas,
            'solo_no_aprobadas' => $soloNoAprobadas,
            'solo_hoy' => $soloHoy,
            'incluir_radicadas_por' => $incluirRadicadasPor,
        ];

        $perPage = (int) ($payload['per_page'] ?? 20);

        if ($documentoPersona === $documentoActor && $incluirRadicadasPor) {
            $novedades = $this->novedadService->obtenerMisNovedadesPaginadas($documentoActor, $filtros, $perPage);
        } else {
            $novedades = $this->novedadService->obtenerNovedadesPersonaPaginadas($documentoPersona, $filtros, $perPage);
        }

        $adjuntosPorNovedad = $this->obtenerAdjuntosPorNovedadesPaginadas($novedades);

        return response()->json([
            'ok' => true,
            'data' => $novedades->getCollection()->map(function ($novedad) use ($adjuntosPorNovedad) {
                $idNovedad = trim((string) ($novedad->id_novedad ?? ''));
                $adjuntos = $adjuntosPorNovedad[$idNovedad] ?? [];

                return $this->mapNovedadParaApi($novedad, $adjuntos);
            })->values(),
            'meta' => $this->metaPaginador($novedades),
        ]);
    }

    public function aprobarJefe(Request $request, string $idNovedad): JsonResponse
    {
        $input = $this->normalizarInputActorDesdeRequest($request);

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'nombre_actor' => 'nullable|string|max:200',
        ], [
            'documento_actor.required' => 'El documento_actor es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $nombreActor = $this->resolverNombreActor(
            isset($payload['nombre_actor']) ? trim((string) $payload['nombre_actor']) : null,
            $documentoActor,
            $payload
        );
        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la solicitud.',
            ], 404);
        }

        $tipoNovedad = $this->resolverTipoNovedad($novedad);
        $resultado = match ($tipoNovedad) {
            NovedadTipoResolver::TIPO_PERMISO => $this->permisoService->aprobarPorJefe(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_PERMISO_PERMANENTE => $this->permisoPermanenteService->aprobarPorJefe(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_VACACION => $this->vacacionService->aprobarPorJefe(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            default => [
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta aprobacion de nivel inicial por este endpoint.',
                'http_status' => 422,
            ],
        };

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible aprobar por jefe.'),
            ], $this->httpStatusResultado($resultado));
        }

        $actor = [
            'documento' => $documentoActor,
            'nombre' => $nombreActor,
        ];
        $correoRrhhEnviado = $tipoNovedad === NovedadTipoResolver::TIPO_PERMISO
            ? $this->notificacionService->enviarPermisoPendienteRrhh($idNovedad, $actor, canal: 'API')
            : ($tipoNovedad === NovedadTipoResolver::TIPO_PERMISO_PERMANENTE
                ? $this->enviarCorreoNotificacionPendienteRrhhPermisoPermanente(
                    idNovedad: $idNovedad,
                    actor: $actor
                )
                : (
                    $tipoNovedad === NovedadTipoResolver::TIPO_VACACION
                    && (bool) ($resultado['requiere_aprobacion_rrhh'] ?? false)
                        ? $this->enviarCorreoNotificacionPendienteRrhhVacacion(
                            idNovedad: $idNovedad,
                            actor: $actor
                        )
                        : false
                ));

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Solicitud aprobada correctamente.'),
            'data' => [
                'id_novedad' => $idNovedad,
                'tipo' => $tipoNovedad,
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_JEFE_APROBADO),
                'aprobado_por' => $actor,
                'notificacion_rrhh_email_enviada' => $correoRrhhEnviado,
            ],
        ]);
    }

    public function gestionarJefe(Request $request, string $idNovedad): JsonResponse
    {
        $input = $this->normalizarInputActorDesdeRequest($request);

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'nombre_actor' => 'nullable|string|max:200',
            'accion' => 'required|string|in:APROBAR,RECHAZAR',
            'motivo_rechazo' => 'nullable|string|max:500',
        ], [
            'documento_actor.required' => 'El documento_actor es obligatorio.',
            'accion.required' => 'La accion es obligatoria.',
            'accion.in' => 'La accion solo puede ser APROBAR o RECHAZAR.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $accion = strtoupper(trim((string) ($payload['accion'] ?? '')));
        $motivoRechazo = trim((string) ($payload['motivo_rechazo'] ?? ''));
        if ($accion === 'RECHAZAR' && $motivoRechazo === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El motivo_rechazo es obligatorio cuando la accion es RECHAZAR.',
            ], 422);
        }

        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $nombreActor = $this->resolverNombreActor(
            isset($payload['nombre_actor']) ? trim((string) $payload['nombre_actor']) : null,
            $documentoActor,
            $payload
        );
        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la solicitud.',
            ], 404);
        }

        $tipoNovedad = $this->resolverTipoNovedad($novedad);

        if ($accion === 'APROBAR') {
            $resultado = match ($tipoNovedad) {
                NovedadTipoResolver::TIPO_PERMISO => $this->permisoService->aprobarPorJefe(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
                NovedadTipoResolver::TIPO_PERMISO_PERMANENTE => $this->permisoPermanenteService->aprobarPorJefe(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
                NovedadTipoResolver::TIPO_VACACION => $this->vacacionService->aprobarPorJefe(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
                default => [
                    'ok' => false,
                    'message' => 'El tipo de solicitud no soporta gestion inicial por este endpoint.',
                    'http_status' => 422,
                ],
            };

            if (! ($resultado['ok'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'message' => (string) ($resultado['message'] ?? 'No fue posible aprobar por jefe.'),
                ], $this->httpStatusResultado($resultado));
            }

            $actor = [
                'documento' => $documentoActor,
                'nombre' => $nombreActor,
            ];
            $correoRrhhEnviado = $tipoNovedad === NovedadTipoResolver::TIPO_PERMISO
                ? $this->notificacionService->enviarPermisoPendienteRrhh($idNovedad, $actor, canal: 'API')
                : ($tipoNovedad === NovedadTipoResolver::TIPO_PERMISO_PERMANENTE
                    ? $this->enviarCorreoNotificacionPendienteRrhhPermisoPermanente(
                        idNovedad: $idNovedad,
                        actor: $actor
                    )
                    : (
                        $tipoNovedad === NovedadTipoResolver::TIPO_VACACION
                        && (bool) ($resultado['requiere_aprobacion_rrhh'] ?? false)
                            ? $this->enviarCorreoNotificacionPendienteRrhhVacacion(
                                idNovedad: $idNovedad,
                                actor: $actor
                            )
                            : false
                    ));

            return response()->json([
                'ok' => true,
                'message' => (string) ($resultado['message'] ?? 'Solicitud aprobada correctamente.'),
                'data' => [
                    'id_novedad' => $idNovedad,
                    'tipo' => $tipoNovedad,
                    'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_JEFE_APROBADO),
                    'accion' => 'APROBAR',
                    'gestionado_por' => $actor,
                    'notificacion_rrhh_email_enviada' => $correoRrhhEnviado,
                ],
            ]);
        }

        $resultado = match ($tipoNovedad) {
            NovedadTipoResolver::TIPO_PERMISO => $this->permisoService->rechazarPermiso(
                idNovedad: $idNovedad,
                nivel: 'jefe',
                motivoRechazo: $motivoRechazo,
                documentoActor: $documentoActor,
                esRrhh: (bool) $permisosActor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_PERMISO_PERMANENTE => $this->permisoPermanenteService->rechazarPermisoPermanente(
                idNovedad: $idNovedad,
                nivel: 'jefe',
                motivoRechazo: $motivoRechazo,
                documentoActor: $documentoActor,
                esRrhh: (bool) $permisosActor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_VACACION => $this->vacacionService->rechazarVacacion(
                idNovedad: $idNovedad,
                nivel: 'jefe',
                motivoRechazo: $motivoRechazo,
                documentoActor: $documentoActor,
                esRrhh: (bool) $permisosActor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            default => [
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta gestion inicial por este endpoint.',
                'http_status' => 422,
            ],
        };

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible rechazar por jefe.'),
            ], $this->httpStatusResultado($resultado));
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Solicitud rechazada correctamente.'),
            'data' => [
                'id_novedad' => $idNovedad,
                'tipo' => $tipoNovedad,
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_RECHAZADO),
                'accion' => 'RECHAZAR',
                'gestionado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => $nombreActor,
                ],
            ],
        ]);
    }

    public function aprobarRrhh(Request $request, string $idNovedad): JsonResponse
    {
        $input = $this->normalizarInputActorDesdeRequest($request);

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'nombre_actor' => 'nullable|string|max:200',
        ], [
            'documento_actor.required' => 'El documento_actor es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $nombreActor = $this->resolverNombreActor(
            isset($payload['nombre_actor']) ? trim((string) $payload['nombre_actor']) : null,
            $documentoActor,
            $payload
        );
        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $puedeGestionarRrhh = (bool) $permisosActor['puede_gestionar_permisos_rrhh']
            || $this->requestTieneScope($request, 'empleados.permisos.rrhh');
        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la solicitud.',
            ], 404);
        }

        $tipoNovedad = $this->resolverTipoNovedad($novedad);

        $resultado = match ($tipoNovedad) {
            NovedadTipoResolver::TIPO_PERMISO => $this->permisoService->aprobarPorRrhh(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esRrhh: $puedeGestionarRrhh,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_PERMISO_PERMANENTE => $this->permisoPermanenteService->aprobarPorRrhh(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esRrhh: $puedeGestionarRrhh,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            NovedadTipoResolver::TIPO_VACACION => $this->vacacionService->aprobarPorRrhh(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                esRrhh: $puedeGestionarRrhh,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            default => [
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta aprobacion RRHH por este endpoint.',
                'http_status' => 422,
            ],
        };

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible aprobar por RRHH.'),
            ], $this->httpStatusResultado($resultado));
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Solicitud aprobada por RRHH correctamente.'),
            'data' => [
                'id_novedad' => $idNovedad,
                'tipo' => $tipoNovedad,
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_APROBADO),
                'aprobado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => $nombreActor,
                ],
            ],
        ]);
    }

    public function gestionarRrhh(Request $request, string $idNovedad, BloqueoService $bloqueoService): JsonResponse
    {
        $input = $this->normalizarInputActorDesdeRequest($request);

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'nombre_actor' => 'nullable|string|max:200',
            'accion' => 'required|string|in:APROBAR,RECHAZAR',
            'motivo_rechazo' => 'nullable|string|max:500',
        ], [
            'documento_actor.required' => 'El documento_actor es obligatorio.',
            'accion.required' => 'La accion es obligatoria.',
            'accion.in' => 'La accion solo puede ser APROBAR o RECHAZAR.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $accion = strtoupper(trim((string) ($payload['accion'] ?? '')));
        $motivoRechazo = trim((string) ($payload['motivo_rechazo'] ?? ''));
        if ($accion === 'RECHAZAR' && $motivoRechazo === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El motivo_rechazo es obligatorio cuando la accion es RECHAZAR.',
            ], 422);
        }

        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $nombreActor = $this->resolverNombreActor(
            isset($payload['nombre_actor']) ? trim((string) $payload['nombre_actor']) : null,
            $documentoActor,
            $payload
        );
        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);
        $puedeGestionarRrhh = (bool) $permisosActor['puede_gestionar_permisos_rrhh']
            || $this->requestTieneScope($request, 'empleados.permisos.rrhh');
        if (! $puedeGestionarRrhh && ! $permisosActor['es_super_admin']) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes autorizacion de RRHH para gestionar solicitudes.',
            ], 403);
        }

        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la solicitud.',
            ], 404);
        }

        $tipoNovedad = $this->resolverTipoNovedad($novedad);
        if ($tipoNovedad === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta gestion RRHH por este endpoint.',
            ], 422);
        }

        $resultado = match ($tipoNovedad) {
            'PERMISO' => $accion === 'APROBAR'
                ? $this->permisoService->aprobarPorRrhh(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                )
                : $this->permisoService->rechazarPermiso(
                    idNovedad: $idNovedad,
                    nivel: 'rrhh',
                    motivoRechazo: $motivoRechazo,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
            'PERMISO_PERMANENTE' => $accion === 'APROBAR'
                ? $this->permisoPermanenteService->aprobarPorRrhh(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                )
                : $this->permisoPermanenteService->rechazarPermisoPermanente(
                    idNovedad: $idNovedad,
                    nivel: 'rrhh',
                    motivoRechazo: $motivoRechazo,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
            'INCAPACIDAD' => $accion === 'APROBAR'
                ? $this->incapacidadService->aprobarSolicitud(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    bloqueoService: $bloqueoService
                )
                : $this->incapacidadService->rechazarSolicitud(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    motivo: $motivoRechazo
                ),
            'VACACION' => $accion === 'APROBAR'
                ? $this->vacacionService->aprobarPorRrhh(
                    idNovedad: $idNovedad,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                )
                : $this->vacacionService->rechazarVacacion(
                    idNovedad: $idNovedad,
                    nivel: 'rrhh',
                    motivoRechazo: $motivoRechazo,
                    documentoActor: $documentoActor,
                    esRrhh: $puedeGestionarRrhh,
                    esSuperAdmin: (bool) $permisosActor['es_super_admin']
                ),
            default => [
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta gestion RRHH por este endpoint.',
                'http_status' => 422,
            ],
        };

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible gestionar la solicitud por RRHH.'),
            ], $this->httpStatusResultado($resultado));
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Solicitud gestionada por RRHH correctamente.'),
            'data' => [
                'id_novedad' => $idNovedad,
                'tipo' => $tipoNovedad,
                'accion' => $accion,
                'estado' => (string) ($resultado['estado'] ?? ($accion === 'APROBAR'
                    ? EmpleadoPermisoService::ESTADO_APROBADO
                    : EmpleadoPermisoService::ESTADO_RECHAZADO)),
                'gestionado_por' => [
                    'documento' => $documentoActor,
                    'nombre' => $nombreActor,
                ],
            ],
        ]);
    }

    public function anularSolicitud(Request $request, string $idNovedad): JsonResponse
    {
        $input = $this->normalizarInputActorDesdeRequest($request);

        $validator = validator($input, [
            'documento_actor' => 'required|string|max:50',
            'motivo_anulacion' => 'required|string|max:500',
            'nombre_actor' => 'nullable|string|max:200',
        ], [
            'documento_actor.required' => 'El documento_actor es obligatorio.',
            'motivo_anulacion.required' => 'El motivo de anulacion es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validacion.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $documentoActorInput = trim((string) ($payload['documento_actor'] ?? ''));
        $seguridadActor = $this->resolverDocumentoActorAsegurado($request, $documentoActorInput);
        if (! ($seguridadActor['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($seguridadActor['message'] ?? 'Forbidden.'),
            ], (int) ($seguridadActor['status'] ?? 403));
        }
        $documentoActor = (string) ($seguridadActor['documento'] ?? '');
        $motivoAnulacion = trim((string) ($payload['motivo_anulacion'] ?? ''));
        $permisosActor = $this->resolverPermisosActorPorDocumento($documentoActor);

        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro la solicitud.',
            ], 404);
        }

        $tipoNovedad = $this->resolverTipoNovedad($novedad);

        $resultado = match ($tipoNovedad) {
            'PERMISO' => $this->permisoService->anularPermiso(
                idNovedad: $idNovedad,
                motivoAnulacion: $motivoAnulacion,
                documentoActor: $documentoActor,
                esRrhh: (bool) $permisosActor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            'PERMISO_PERMANENTE' => $this->permisoPermanenteService->anularPermisoPermanente(
                idNovedad: $idNovedad,
                motivoAnulacion: $motivoAnulacion,
                documentoActor: $documentoActor,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            'INCAPACIDAD' => $this->incapacidadService->anularSolicitud(
                idNovedad: $idNovedad,
                documentoActor: $documentoActor,
                motivoAnulacion: $motivoAnulacion,
                esRrhh: (bool) $permisosActor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            'VACACION' => $this->vacacionService->anularVacacion(
                idNovedad: $idNovedad,
                motivoAnulacion: $motivoAnulacion,
                documentoActor: $documentoActor,
                esSuperAdmin: (bool) $permisosActor['es_super_admin']
            ),
            default => [
                'ok' => false,
                'message' => 'El tipo de solicitud no soporta anulacion por este endpoint.',
                'http_status' => 422,
            ],
        };

        if (! ($resultado['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($resultado['message'] ?? 'No fue posible anular la solicitud.'),
            ], $this->httpStatusResultado($resultado));
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($resultado['message'] ?? 'Solicitud anulada correctamente.'),
            'data' => [
                'id_novedad' => $idNovedad,
                'tipo' => $tipoNovedad,
                'estado' => (string) ($resultado['estado'] ?? EmpleadoPermisoService::ESTADO_ANULADO),
                'anulado_por' => [
                    'documento' => $documentoActor,
                ],
            ],
        ]);
    }

    public function schemaRadicar(Request $request): JsonResponse
    {
        try {
            $validator = validator([], EmpleadoPermisoService::reglasCreacion());
            $validator->validate();
        } catch (ValidationException $e) {
            $mensajes = $this->mensajesUnicosDesdeValidator($e->validator);
        }

        $mensajes ??= EmpleadoPermisoService::mensajesCreacion();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'multipart/form-data',
        ];
        if ($request->is('api/v2/*')) {
            $headers['Authorization'] = 'Bearer {jwt_token}';
        } else {
            $headers['X-API-KEY'] = 'required';
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'metodo' => 'POST',
                'endpoint' => $request->is('api/v2/*')
                    ? '/api/v2/empleados/permisos/radicar'
                    : '/api/v1/empleados/permisos/radicar',
                'headers' => $headers,
                'campos_requeridos' => [
                    'documento_actor' => 'string (quien radica)',
                    'documento_persona' => 'string',
                    'fecha_permiso' => 'Y-m-d',
                    'hora_salida' => 'H:i',
                    'motivo' => implode(', ', array_keys(EmpleadoPermisoService::opcionesMotivo())),
                    'actividad' => 'string',
                ],
                'campos_opcionales' => [
                    'nombre_actor' => 'string',
                    'hora_ingreso' => 'H:i',
                    'otro_motivo' => 'required when motivo=OTROS',
                    'sistema_origen' => 'string',
                    'ip_equipo' => 'string',
                    'adjuntos[][tipo_documento_id]' => 'string',
                    'adjuntos[][archivo]' => 'file(pdf,jpg,jpeg,png|max:10MB)',
                    'aliases_compatibilidad' => 'documento_radica, documento_usuario, identificacion, nombre_usuario',
                ],
                'mensajes_validacion' => $mensajes,
            ],
        ]);
    }

    public function schemaAprobaciones(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer {jwt_token}',
                ],
                'jefe' => [
                    'scope_requerido' => 'empleados.permisos.jefe',
                    'listar_pendientes' => [
                        'metodo' => 'GET',
                        'endpoint' => '/api/v2/empleados/solicitudes/equipo-jefe?documento_actor={documento_jefe}',
                    ],
                    'aprobar' => [
                        'metodo' => 'POST',
                        'endpoint' => '/api/v2/empleados/solicitudes/{id_novedad}/gestionar-jefe',
                        'body' => [
                            'documento_actor' => 'string',
                            'nombre_actor' => 'string opcional',
                            'accion' => 'APROBAR|RECHAZAR',
                            'motivo_rechazo' => 'string requerido cuando accion=RECHAZAR',
                        ],
                    ],
                ],
                'rrhh' => [
                    'scope_requerido' => 'empleados.permisos.rrhh',
                    'listar_pendientes' => [
                        'metodo' => 'GET',
                        'endpoint' => '/api/v2/empleados/solicitudes/pendientes-rrhh?documento_actor={documento_rrhh}',
                    ],
                    'gestionar_unificado' => [
                        'metodo' => 'POST',
                        'endpoint' => '/api/v2/empleados/solicitudes/{id_novedad}/gestionar-rrhh',
                        'body' => [
                            'documento_actor' => 'string',
                            'nombre_actor' => 'string opcional',
                            'accion' => 'APROBAR|RECHAZAR',
                            'motivo_rechazo' => 'string requerido cuando accion=RECHAZAR',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function schemaRadicarVacaciones(Request $request): JsonResponse
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'multipart/form-data',
        ];

        if ($request->is('api/v2/*')) {
            $headers['Authorization'] = 'Bearer {jwt_token}';
        } else {
            $headers['X-API-KEY'] = 'required';
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'metodo' => 'POST',
                'endpoint' => $request->is('api/v2/*')
                    ? '/api/v2/empleados/vacaciones/radicar'
                    : '/api/v1/empleados/vacaciones/radicar',
                'headers' => $headers,
                'campos_requeridos' => [
                    'documento_actor' => 'string (quien radica)',
                    'documento_persona' => 'string',
                    'fecha_inicio' => 'Y-m-d',
                    'fecha_fin' => 'Y-m-d (>= fecha_inicio)',
                    'carta' => 'file(pdf,jpg,jpeg,png|max:10MB)',
                ],
                'campos_opcionales' => [
                    'nombre_actor' => 'string',
                    'observacion' => 'string|max:1000',
                    'sistema_origen' => 'string',
                    'ip_equipo' => 'string',
                    'aliases_compatibilidad' => 'documento_radica, documento_usuario, identificacion, nombre_usuario',
                ],
                'scope_requerido_v2' => 'empleados.permisos',
            ],
        ]);
    }

    public function schemaRadicarPermisoPermanente(Request $request): JsonResponse
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'multipart/form-data',
        ];

        if ($request->is('api/v2/*')) {
            $headers['Authorization'] = 'Bearer {jwt_token}';
        } else {
            $headers['X-API-KEY'] = 'required';
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'metodo' => 'POST',
                'endpoint' => $request->is('api/v2/*')
                    ? '/api/v2/empleados/permisos-permanentes/radicar'
                    : '/api/v1/empleados/permisos-permanentes/radicar',
                'headers' => $headers,
                'campos_requeridos' => [
                    'documento_actor' => 'string (quien radica)',
                    'documento_persona' => 'string',
                    'fecha_inicio' => 'Y-m-d',
                    'fecha_fin' => 'Y-m-d (>= fecha_inicio)',
                    'jornada' => 'MANANA | TARDE | AMBAS (compatibilidad: 1 | 2 | 1,2)',
                    'carta_solicitud' => 'file(pdf,jpg,jpeg,png|max:10MB)',
                    'documento_soporte' => 'file(pdf,jpg,jpeg,png|max:10MB)',
                ],
                'campos_opcionales' => [
                    'horario_fijo' => '0|1',
                    'hora_salida_j1' => 'H:i (si aplica jornada 1 con horario fijo)',
                    'hora_ingreso_j1' => 'H:i (si aplica jornada 1 con horario fijo)',
                    'hora_salida_j2' => 'H:i (si aplica jornada 2 con horario fijo)',
                    'hora_ingreso_j2' => 'H:i (si aplica jornada 2 con horario fijo)',
                    'observacion' => 'string|max:1000',
                    'nombre_actor' => 'string',
                    'sistema_origen' => 'string',
                    'ip_equipo' => 'string',
                    'aliases_compatibilidad' => 'documento_radica, documento_usuario, identificacion, nombre_usuario',
                ],
                'scope_requerido_v2' => 'empleados.permisos',
            ],
        ]);
    }

    private function mapPermisoParaApi(mixed $permiso): array
    {
        return [
            'id_novedad' => (string) ($permiso->emp_novedad_id ?? ''),
            'documento_empleado' => (string) ($permiso->id_persona ?? ''),
            'nombre_empleado' => (string) ($permiso->persona_nombre ?? ''),
            'radicado_por' => [
                'documento' => (string) ($permiso->radicado_por_documento ?? ''),
                'nombre' => (string) ($permiso->radicado_por_nombre ?? ''),
            ],
            'estado_flujo' => (string) ($permiso->estado_flujo ?? ''),
            'estado_label' => EmpleadoPermisoService::opcionesEstadoFlujo()[(string) ($permiso->estado_flujo ?? '')] ?? (string) ($permiso->estado_flujo ?? ''),
            'fecha_permiso' => $this->formatearFecha($permiso->fecha_inicio ?? null, 'Y-m-d'),
            'hora_salida' => $this->formatearFecha($permiso->fecha_inicio ?? null, 'H:i'),
            'hora_ingreso' => $this->formatearFecha($permiso->fecha_fin ?? null, 'H:i'),
            'motivo' => (string) ($permiso->motivo_label ?? ''),
            'actividad' => (string) ($permiso->actividad ?? ''),
            'created_at' => $this->formatearFecha($permiso->fecha_creacion ?? null, 'Y-m-d H:i:s'),
        ];
    }

    private function mapNovedadParaApi(mixed $novedad, array $adjuntos = []): array
    {
        $tipoLabel = (string) ($novedad->tipo_label ?? '');
        if ($tipoLabel === '') {
            $tipoLabel = (string) ($novedad->tipo_codigo ?? '');
        }

        return [
            'id_novedad' => (string) ($novedad->id_novedad ?? ''),
            'id_origen' => (string) ($novedad->id_origen ?? ''),
            'tipo' => $tipoLabel,
            'estado' => (string) ($novedad->estado ?? ''),
            'documento_persona' => (string) ($novedad->documento_persona ?? $novedad->id_persona ?? ''),
            'persona_nombre' => (string) ($novedad->persona_nombre ?? ''),
            'documento_radica' => (string) ($novedad->documento_radica ?? $novedad->radicado_por_documento ?? ''),
            'radicado_por_nombre' => (string) ($novedad->radicado_por_nombre ?? ''),
            'fecha_inicio' => $this->formatearFecha($novedad->fecha_inicio ?? null, 'Y-m-d H:i:s'),
            'fecha_fin' => $this->formatearFecha($novedad->fecha_fin ?? null, 'Y-m-d H:i:s'),
            'fecha_creacion' => $this->formatearFecha($novedad->fecha_creacion ?? null, 'Y-m-d H:i:s'),
            'detalle_descripcion' => (string) ($novedad->detalle_descripcion ?? ''),
            'adjuntos_count' => (int) ($novedad->adjuntos_count ?? 0),
            'tiene_adjuntos' => (bool) ($novedad->tiene_adjuntos ?? false),
            'adjuntos' => array_values($adjuntos),
        ];
    }

    private function mapSolicitudParaApi(mixed $novedad, array $adjuntos = []): array
    {
        $data = $this->mapNovedadParaApi($novedad, $adjuntos);
        $estado = (string) ($data['estado'] ?? '');

        $data['documento_empleado'] = (string) ($data['documento_persona'] ?? '');
        $data['nombre_empleado'] = (string) ($data['persona_nombre'] ?? '');
        $data['estado_flujo'] = $estado;
        $data['estado_label'] = EmpleadoPermisoService::opcionesEstadoFlujo()[$estado] ?? $estado;
        $data['radicado_por'] = [
            'documento' => (string) ($data['documento_radica'] ?? ''),
            'nombre' => (string) ($data['radicado_por_nombre'] ?? ''),
        ];

        return $data;
    }

    private function responderListadoNovedades($novedades): JsonResponse
    {
        $adjuntosPorNovedad = $this->obtenerAdjuntosPorNovedadesPaginadas($novedades);

        return response()->json([
            'ok' => true,
            'data' => $novedades->getCollection()->map(function ($novedad) use ($adjuntosPorNovedad) {
                $idNovedad = trim((string) ($novedad->id_novedad ?? ''));
                $adjuntos = $adjuntosPorNovedad[$idNovedad] ?? [];

                return $this->mapSolicitudParaApi($novedad, $adjuntos);
            })->values(),
            'meta' => $this->metaPaginador($novedades),
        ]);
    }

    private function obtenerAdjuntosPorNovedadesPaginadas($novedades): array
    {
        $idsNovedad = $novedades->getCollection()
            ->pluck('id_novedad')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $this->documentoService->obtenerAdjuntosPorNovedades($idsNovedad);
    }

    private function metaPaginador($paginador): array
    {
        return [
            'current_page' => $paginador->currentPage(),
            'per_page' => $paginador->perPage(),
            'last_page' => $paginador->lastPage(),
            'total' => $paginador->total(),
            'count' => $paginador->count(),
            'has_more' => $paginador->hasMorePages(),
            'next_page_url' => $paginador->nextPageUrl(),
            'prev_page_url' => $paginador->previousPageUrl(),
        ];
    }

    private function resolverPerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) {
            return 20;
        }

        if ($perPage > 100) {
            return 100;
        }

        return $perPage;
    }

    private function httpStatusResultado(array $resultado): int
    {
        $message = mb_strtolower((string) ($resultado['message'] ?? ''), 'UTF-8');
        if (str_contains($message, 'no se encontro')) {
            return 404;
        }

        if (str_contains($message, 'no tienes autorizacion')) {
            return 403;
        }

        if (str_contains($message, 'no esta pendiente') || str_contains($message, 'otro usuario')) {
            return 409;
        }

        if (str_contains($message, 'no puede anularse') || str_contains($message, 'estado actual')) {
            return 409;
        }

        return (int) ($resultado['http_status'] ?? 422);
    }

    private function resolverTipoNovedad(object $novedad): string
    {
        return NovedadTipoResolver::normalizar(
            (string) ($novedad->tipo_tabla_db ?? ''),
            (string) ($novedad->tipo_descripcion ?? '')
        );
    }

    private function formatearFecha(mixed $valor, string $formato): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->format($formato);
        } catch (\Throwable) {
            return trim((string) $valor) !== '' ? trim((string) $valor) : null;
        }
    }

    private function mensajesUnicosDesdeValidator(Validator $validator): array
    {
        $mensajes = [];
        foreach ($validator->errors()->messages() as $campo => $errores) {
            if (! isset($errores[0])) {
                continue;
            }

            $mensajes[$campo] = (string) $errores[0];
        }

        return $mensajes;
    }

    private function resolverPermisosActorPorDocumento(string $documento): array
    {
        $usuario = $this->actorNovedadService->buscarUsuarioActivoPorDocumento($documento);

        return [
            'usuario' => $usuario,
            'es_rrhh' => $this->permisoService->esUsuarioRrhh($usuario),
            'es_super_admin' => $this->permisoService->esUsuarioSuperAdmin($usuario),
            'puede_gestionar_permisos_rrhh' => $usuario
                ? $usuario->can(Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                : false,
        ];
    }

    private function requestTieneScope(Request $request, string $scope): bool
    {
        $scope = trim($scope);
        if ($scope === '') {
            return false;
        }

        $scopes = $request->attributes->get('api_jwt_scopes', []);
        if (! is_array($scopes)) {
            return false;
        }

        $scopesNormalizados = array_map(
            static fn ($value) => trim((string) $value),
            $scopes
        );

        $adminScopes = config('services.api_jwt.admin_scopes', []);
        if (is_array($adminScopes) && $adminScopes !== []) {
            $adminScopesNormalizados = array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                $adminScopes
            )));

            foreach ($adminScopesNormalizados as $scopeAdmin) {
                if (in_array($scopeAdmin, $scopesNormalizados, true)) {
                    return true;
                }
            }
        }

        return in_array($scope, $scopesNormalizados, true);
    }

    private function resolverDocumentoActorAsegurado(Request $request, string $documentoRequest): array
    {
        $documentoRequest = trim($documentoRequest);
        $documentoToken = $this->obtenerDocumentoActorToken($request);
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

    private function obtenerDocumentoActorToken(Request $request): string
    {
        $claims = $request->attributes->get('api_jwt_claims', []);
        $claims = is_array($claims) ? $claims : [];

        return trim((string) ($claims['actor_documento'] ?? ''));
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
            $persona = $this->permisoService->buscarPersona($documento);
            $nombre = trim((string) ($persona['nombre'] ?? ''));
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return null;
    }

    private function enviarCorreoNotificacionVacacionesRadicadas(
        string $idNovedad,
        array $resultado,
        array $payload,
        string $documentoActor,
        ?string $nombreActor
    ): bool {
        return $this->notificacionService->enviarVacacionRadicada(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            urlAccion: URL::to('/gestionRRHH/permisos/jefe'),
            canal: 'API'
        );
    }

    private function enviarCorreoNotificacionPermisoPermanenteRadicado(
        string $idNovedad,
        array $resultado,
        array $payload,
        string $documentoActor,
        ?string $nombreActor
    ): bool {
        return $this->notificacionService->enviarPermisoPermanenteRadicado(
            idNovedad: $idNovedad,
            resultado: $resultado,
            payload: $payload,
            documentoActor: $documentoActor,
            nombreActor: $nombreActor,
            urlAccion: URL::to('/gestionRRHH/permisos/jefe'),
            canal: 'API'
        );
    }

    private function enviarCorreoNotificacionPendienteRrhhPermisoPermanente(string $idNovedad, array $actor): bool
    {
        return $this->notificacionService->enviarPendienteRrhhPermisoPermanente(
            idNovedad: $idNovedad,
            actor: $actor,
            urlAccion: URL::to('/gestionRRHH/permisos/rrhh'),
            canal: 'API'
        );
    }

    private function enviarCorreoNotificacionPendienteRrhhVacacion(string $idNovedad, array $actor): bool
    {
        return $this->notificacionService->enviarPendienteRrhhVacacion(
            idNovedad: $idNovedad,
            actor: $actor,
            urlAccion: URL::to('/gestionRRHH/permisos/rrhh'),
            canal: 'API'
        );
    }

    private function normalizarInputActorDesdeRequest(Request $request): array
    {
        $input = $this->normalizarInputActor($request->all());
        $documentoActor = trim((string) ($input['documento_actor'] ?? ''));
        if ($documentoActor === '') {
            $documentoActor = $this->obtenerDocumentoActorToken($request);
        }

        $input['documento_actor'] = $documentoActor;
        if (trim((string) ($input['documento_usuario'] ?? '')) === '') {
            $input['documento_usuario'] = $documentoActor;
        }

        return $input;
    }

    private function normalizarInputActor(array $input): array
    {
        $documentoActor = trim((string) ($input['documento_actor'] ?? ''));
        $documentoUsuario = trim((string) ($input['documento_usuario'] ?? ''));
        $documentoRadica = trim((string) ($input['documento_radica'] ?? ''));
        $documento = trim((string) ($input['documento'] ?? ''));
        $nombreActor = trim((string) ($input['nombre_actor'] ?? ''));
        $nombreUsuario = trim((string) ($input['nombre_usuario'] ?? ''));

        $actor = $documentoActor !== ''
            ? $documentoActor
            : ($documentoUsuario !== '' ? $documentoUsuario : ($documentoRadica !== '' ? $documentoRadica : $documento));

        $nombre = $nombreActor !== ''
            ? $nombreActor
            : $nombreUsuario;

        $input['documento_actor'] = $actor;
        $input['documento_usuario'] = $actor;
        $input['nombre_actor'] = $nombre;
        $input['nombre_usuario'] = $nombre;

        return $input;
    }

    private function resolverDocumentoRequest(Request $request, array $keys, bool $soloQuery = false): string
    {
        foreach ($keys as $key) {
            $valor = $soloQuery
                ? $request->query($key, '')
                : $request->input($key, '');

            $valor = trim((string) $valor);
            if ($valor !== '') {
                return $valor;
            }
        }

        return '';
    }
}

