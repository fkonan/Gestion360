<?php

namespace App\Modules\GestionRRHH\Http\Controllers\Novedades;

use App\Constants\Permisos;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DocumentalStorageService;
use App\Modules\GestionRRHH\Http\Requests\AnularEmpleadoPermisoRequest;
use App\Modules\GestionRRHH\Http\Requests\RechazarEmpleadoPermisoRequest;
use App\Modules\GestionRRHH\Http\Requests\StoreEmpleadoPermisoRequest;
use App\Modules\GestionRRHH\Http\Requests\UpdateEmpleadoNovedadHorasRequest;
use App\Modules\GestionRRHH\Services\Novedades\ActorNovedadService;
use App\Modules\GestionRRHH\Services\Novedades\EmpleadoNovedadService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadTipoResolver;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoPdfService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EmpleadoSolicitudController extends Controller
{
    private const MAGIC_LINK_CACHE_PREFIX = 'empleados_permisos_magic_link:';
    private const ESTADOS_LISTA_NO_APROBADAS = [
        'RADICADO' => 'Radicado',
        'JEFE_APROBADO' => 'Jefe aprobado',
        'RECHAZADO' => 'Rechazado',
        'ANULADO' => 'Anulado',
    ];
    private const ESTADOS_LISTA_SOLO_APROBADAS = [
        'APROBADO' => 'Aprobado',
    ];
    private const ESTADOS_LISTA_RRHH = [
        'RADICADO' => 'Radicado',
        'JEFE_APROBADO' => 'Jefe aprobado',
    ];

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly EmpleadoNovedadService $novedadService,
        private readonly ActorNovedadService $actorNovedadService,
        private readonly NovedadNotificacionService $notificacionService,
        private readonly EmpleadoPermisoPdfService $permisoPdfService,
        private readonly EmpleadoPermisoDocumentoService $documentoService,
        private readonly EmpleadoPermisoPermanenteService $permisoPermanenteService,
        private readonly EmpleadoPermisoPermanenteDocumentoService $permisoPermanenteDocumentoService,
        private readonly EmpleadoVacacionService $vacacionService,
        private readonly EmpleadoVacacionDocumentoService $vacacionDocumentoService,
        private readonly EmpleadoIncapacidadService $incapacidadService,
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public function index(Request $request)
    {
        $usuario = $request->user();
        $actor = $this->resolverContextoActor($request);

        return view('gestionrrhh::novedades.index', [
            'puedeGestionarPermisosJefe' => (bool) ($actor['puede_gestionar_solicitudes_equipo'] ?? false),
            'puedeGestionarPermisosRrhh' => $this->puedeGestionarPermisosRrhh($usuario),
            'puedeVerNovedades' => $this->puedeVerNovedades($usuario),
        ]);
    }

    public function create(Request $request)
    {
        $actor = $this->resolverContextoActor($request);

        return view('gestionrrhh::novedades.radicar', [
            'opcionesMotivo' => EmpleadoPermisoService::opcionesMotivo(),
            'tiposDocumento' => $this->documentoService->obtenerTiposDocumento(),
            'documentoUsuarioDefault' => $actor['documento'],
        ]);
    }

    public function radicar()
    {
        return view('gestionrrhh::novedades.radicar_selector', [
            'tiposRadicacion' => $this->novedadService->obtenerTiposRadicacionDisponibles(),
        ]);
    }

    public function novedades(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'persona' => 'nullable|string|max:120',
            'tipo' => 'nullable|string|max:40',
            'solo_hoy' => 'nullable|in:0,1',
            'solo_activas_hoy' => 'nullable|in:0,1',
            'vigencia' => 'nullable|in:todas,inician_hoy,activas_hoy',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('gestionRRHH.permisos.novedades')
                ->withErrors($validator)
                ->withInput();
        }

        $vigencia = trim((string) $request->query('vigencia', ''));
        if ($vigencia === '') {
            if ((string) $request->query('solo_activas_hoy', '0') === '1') {
                $vigencia = 'activas_hoy';
            } elseif ((string) $request->query('solo_hoy', '0') === '1') {
                $vigencia = 'inician_hoy';
            } else {
                $vigencia = 'todas';
            }
        }

        $filtros = [
            'persona' => trim((string) $request->query('persona', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'estado' => EmpleadoPermisoService::ESTADO_APROBADO,
            'solo_aprobadas' => true,
            'solo_hoy' => $vigencia === 'inician_hoy',
            'solo_activas_hoy' => $vigencia === 'activas_hoy',
            'vigencia' => $vigencia,
        ];

        $novedades = $this->novedadService->obtenerNovedadesPaginadas($filtros, 25);

        $puedeGestionarRrhh = $this->puedeGestionarPermisosRrhh($request->user());
        $novedades->getCollection()->transform(function ($novedad) use ($puedeGestionarRrhh) {
            $novedad->inicio_fecha = $this->formatearFechaSolo($novedad->fecha_inicio);
            $novedad->inicio_hora = $this->formatearHora12DesdeValor($novedad->fecha_inicio);
            $novedad->fin_fecha = $this->formatearFechaSolo($novedad->fecha_fin);
            $novedad->fin_hora = $this->formatearHora12DesdeValor($novedad->fecha_fin);
            $novedad->hora_inicio_24 = $this->formatearHora24DesdeValor($novedad->fecha_inicio);
            $novedad->hora_fin_24 = $this->formatearHora24DesdeValor($novedad->fecha_fin);
            $tipo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
            $estado = strtoupper(trim((string) ($novedad->estado ?? '')));
            $novedad->puede_ver_gestion = $puedeGestionarRrhh
                && $tipo === 'INCAPACIDAD'
                && $estado === 'APROBADO'
                && ! empty($novedad->origen_gestion_url);

            return $novedad;
        });

        return view('gestionrrhh::novedades.novedades_lista', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'tiposDisponibles' => $this->tiposDisponibles(),
            'estadosDisponibles' => self::ESTADOS_LISTA_SOLO_APROBADAS,
            'modoVista' => 'global',
            'tituloVista' => 'Novedades aprobadas',
            'breadcrumbFinal' => 'Novedades aprobadas',
            'forzarAprobadas' => true,
        ]);
    }

    public function actualizarHoras(UpdateEmpleadoNovedadHorasRequest $request, string $id)
    {
        $payload = $request->validated();
        $documentoActor = $this->permisoService->obtenerDocumentoUsuario($request->user());

        $resultado = $this->permisoService->actualizarHorasNovedadPermiso(
            idNovedad: $id,
            horaInicio: (string) $payload['hora_inicio'],
            horaFin: isset($payload['hora_fin']) ? (string) $payload['hora_fin'] : null,
            documentoActor: $documentoActor !== '' ? $documentoActor : null
        );

        if (! ($resultado['ok'] ?? false)) {
            if (! empty($resultado['error'])) {
                Log::error('Error actualizando horas de novedad de empleado', [
                    'novedad_id' => $id,
                    'message' => (string) $resultado['error'],
                ]);
            }

            return redirect()->back()->with('error', (string) ($resultado['message'] ?? 'No fue posible actualizar las horas de la novedad.'));
        }

        return redirect()->back()->with('success', (string) ($resultado['message'] ?? 'Horas de la novedad actualizadas correctamente.'));
    }

    public function accesoDirectoJefe(Request $request, string $token)
    {
        $token = trim($token);
        if ($token === '') {
            return redirect()->route('login.form')->with('error', 'El enlace de acceso no es valido.');
        }

        $payload = Cache::pull(self::MAGIC_LINK_CACHE_PREFIX.$token);
        if (! is_array($payload)) {
            return redirect()->route('login.form')->with('error', 'El enlace de acceso ya fue usado o expiro.');
        }

        $documentoJefe = trim((string) ($payload['documento_jefe'] ?? ''));
        if ($documentoJefe === '') {
            return redirect()->route('login.form')->with('error', 'No se pudo identificar el jefe para el acceso.');
        }

        $usuarioJefe = $this->actorNovedadService->buscarUsuarioActivoPorDocumento($documentoJefe);
        if (! $usuarioJefe) {
            return redirect()->route('login.form')->with('error', 'No existe un usuario activo asociado al jefe.');
        }

        if (! $this->permisoService->esUsuarioJefe($usuarioJefe) && ! $this->permisoService->esUsuarioSuperAdmin($usuarioJefe)) {
            return redirect()->route('login.form')->with('error', 'El usuario no tiene perfil de jefe para gestionar permisos de equipo.');
        }

        Auth::login($usuarioJefe);
        $request->session()->regenerate();

        return redirect()
            ->route('gestionRRHH.permisos.jefe')
            ->with('success', 'Acceso autorizado mediante enlace seguro.');
    }

    public function misSolicitudes(Request $request)
    {
        $actor = $this->resolverContextoActor($request);
        $documentoActor = trim((string) ($actor['documento'] ?? ''));
        if ($documentoActor === '') {
            return redirect()
                ->route('gestionRRHH.solicitudes.index')
                ->with('error', 'No fue posible identificar tu documento para consultar tus solicitudes.');
        }

        $filtros = [
            'estado' => trim((string) $request->query('estado', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'solo_no_aprobadas' => true,
        ];

        $identificadoresActor = [$documentoActor];

        $novedades = $this->novedadService->obtenerMisSolicitudesPaginadas($identificadoresActor, $filtros, 20);

        $novedades->getCollection()->transform(function ($novedad) use ($request) {
            $estado = (string) ($novedad->estado ?? '');
            $tipo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
            $usuario = $request->user();
            $documentoActor = $this->permisoService->obtenerDocumentoUsuario($usuario);
            $documentoPersona = trim((string) ($novedad->documento_persona ?? ''));

            $puedeAnularPermiso = $tipo === 'PERMISO'
                && $this->permisoService->puedeAnular($usuario, $documentoPersona, $estado);
            $puedeAnularPermisoPermanente = $tipo === 'PERMISO_PERMANENTE'
                && $documentoActor !== ''
                && $documentoActor === $documentoPersona
                && ! in_array($estado, [
                    EmpleadoPermisoPermanenteService::ESTADO_APROBADO,
                    EmpleadoPermisoPermanenteService::ESTADO_RECHAZADO,
                    EmpleadoPermisoPermanenteService::ESTADO_ANULADO,
                ], true);
            $puedeAnularVacacion = $tipo === 'VACACION'
                && $documentoActor !== ''
                && $documentoActor === $documentoPersona
                && ! in_array($estado, [
                    EmpleadoVacacionService::ESTADO_APROBADO,
                    EmpleadoVacacionService::ESTADO_RECHAZADO,
                    EmpleadoVacacionService::ESTADO_ANULADO,
                ], true);
            $puedeAnularIncapacidad = $tipo === 'INCAPACIDAD'
                && $documentoActor !== ''
                && $documentoActor === $documentoPersona
                && ! in_array($estado, [
                    EmpleadoIncapacidadService::ESTADO_APROBADO,
                    EmpleadoIncapacidadService::ESTADO_RECHAZADO,
                    EmpleadoIncapacidadService::ESTADO_ANULADO,
                ], true);

            $novedad->puede_anular = $puedeAnularPermiso || $puedeAnularPermisoPermanente || $puedeAnularVacacion || $puedeAnularIncapacidad;

            return $novedad;
        });

        return view('gestionrrhh::novedades.novedades_lista', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'tiposDisponibles' => $this->tiposDisponibles(),
            'estadosDisponibles' => self::ESTADOS_LISTA_NO_APROBADAS,
            'modoVista' => 'mis',
            'tituloVista' => 'Mis solicitudes',
            'breadcrumbFinal' => 'Mis solicitudes',
            'forzarNoAprobadas' => true,
        ]);
    }

    public function misNovedades(Request $request)
    {
        $actor = $this->resolverContextoActor($request);
        $documentoActor = trim((string) ($actor['documento'] ?? ''));
        if ($documentoActor === '') {
            return redirect()
                ->route('gestionRRHH.solicitudes.index')
                ->with('error', 'No fue posible identificar tu documento para consultar tus novedades.');
        }

        $filtros = [
            'estado' => EmpleadoPermisoService::ESTADO_APROBADO,
            'tipo' => trim((string) $request->query('tipo', '')),
            'solo_aprobadas' => true,
        ];

        $novedades = $this->novedadService->obtenerMisNovedadesPaginadas($documentoActor, $filtros, 20);

        return view('gestionrrhh::novedades.novedades_lista', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'tiposDisponibles' => $this->tiposDisponibles(),
            'estadosDisponibles' => self::ESTADOS_LISTA_SOLO_APROBADAS,
            'modoVista' => 'mis_novedades',
            'tituloVista' => 'Mis novedades',
            'breadcrumbFinal' => 'Mis novedades',
            'forzarAprobadas' => true,
        ]);
    }

    public function solicitudesJefe(Request $request)
    {
        $actor = $this->resolverContextoActor($request);
        if (! $actor['puede_gestionar_solicitudes_equipo']) {
            return redirect()
                ->route('gestionRRHH.solicitudes.index')
                ->with('error', 'No tienes autorizacion para administrar permisos de equipo.');
        }

        $filtros = [
            'estado' => trim((string) $request->query('estado', EmpleadoPermisoService::ESTADO_RADICADO)),
            'tipo' => trim((string) $request->query('tipo', '')),
            'solo_no_aprobadas' => true,
        ];

        $novedades = $this->novedadService->obtenerNovedadesEquipoPaginadas(
            documentoJefe: $actor['documento'],
            filtros: $filtros,
            perPage: 20
        );

        $usuario = $request->user();
        $documentoActor = trim((string) ($actor['documento'] ?? ''));
        $esSuperAdmin = (bool) ($actor['es_super_admin'] ?? false);
        $novedades->getCollection()->transform(function ($novedad) use ($usuario, $documentoActor, $esSuperAdmin) {
            $estadoFlujo = (string) ($novedad->estado ?? '');
            $documentoEmpleado = trim((string) ($novedad->documento_persona ?? $novedad->id_persona ?? ''));
            $tipoCodigo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
            $esPermiso = $tipoCodigo === 'PERMISO';
            $esPermisoPermanente = $tipoCodigo === 'PERMISO_PERMANENTE';
            $esVacacion = $tipoCodigo === 'VACACION';
            $puedeAprobarPermiso = $esPermiso
                && $this->permisoService->puedeAprobarJefe($usuario, $documentoEmpleado, $estadoFlujo);
            $puedeRechazarPermiso = $esPermiso
                && $this->permisoService->puedeRechazarJefe($usuario, $documentoEmpleado, $estadoFlujo);
            $puedeGestionarPermisoPermanente = $esPermisoPermanente
                && $estadoFlujo === EmpleadoPermisoPermanenteService::ESTADO_RADICADO
                && ($esSuperAdmin || $this->permisoService->puedeAprobarJefe($usuario, $documentoEmpleado, $estadoFlujo));

            $tipoAprobadorVacacion = strtoupper(trim((string) ($novedad->tipo_aprobador ?? 'JEFE')));
            $documentoAprobadorVacacion = trim((string) ($novedad->documento_aprobador ?? ''));
            $puedeGestionarVacacionInicial = $esVacacion
                && $estadoFlujo === EmpleadoVacacionService::ESTADO_RADICADO
                && (
                    $esSuperAdmin
                    || (
                        $tipoAprobadorVacacion === 'ASOCIADO'
                        && $documentoActor !== ''
                        && $documentoAprobadorVacacion !== ''
                        && $documentoActor === $documentoAprobadorVacacion
                    )
                    || (
                        $tipoAprobadorVacacion !== 'ASOCIADO'
                        && $this->permisoService->puedeAprobarJefe($usuario, $documentoEmpleado, $estadoFlujo)
                    )
                );

            $novedad->puede_aprobar_jefe = $puedeAprobarPermiso || $puedeGestionarPermisoPermanente || $puedeGestionarVacacionInicial;
            $novedad->puede_rechazar_jefe = $puedeRechazarPermiso || $puedeGestionarPermisoPermanente || $puedeGestionarVacacionInicial;

            return $novedad;
        });

        return view('gestionrrhh::novedades.novedades_lista', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'tiposDisponibles' => $this->tiposDisponibles(),
            'estadosDisponibles' => self::ESTADOS_LISTA_NO_APROBADAS,
            'modoVista' => 'jefe',
            'tituloVista' => 'Solicitudes de mi equipo',
            'breadcrumbFinal' => 'Solicitudes de mi equipo',
            'forzarNoAprobadas' => true,
        ]);
    }

    public function solicitudesPendientesRrhh(Request $request)
    {
        $actor = $this->resolverContextoActor($request);
        if (! $actor['puede_gestionar_permisos_rrhh']) {
            return redirect()
                ->route('gestionRRHH.solicitudes.index')
                ->with('error', 'No tienes autorizacion para gestionar permisos pendientes de RRHH.');
        }

        $filtros = [
            'persona' => trim((string) $request->query('persona', '')),
            'estado' => trim((string) $request->query('estado', '')),
            'tipo' => trim((string) $request->query('tipo', '')),
            'solo_no_aprobadas' => true,
            'solo_gestionables_rrhh' => true,
        ];

        $novedades = $this->novedadService->obtenerNovedadesPaginadas($filtros, 20);

        $puedeGestionarRrhh = (bool) ($actor['puede_gestionar_permisos_rrhh'] ?? false);
        $novedades->getCollection()->transform(function ($novedad) use ($puedeGestionarRrhh) {
            $estadoFlujo = (string) ($novedad->estado ?? '');
            $tipo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
            $novedad->puede_aprobar_rrhh = $puedeGestionarRrhh && (
                ($tipo === 'PERMISO' && $estadoFlujo === EmpleadoPermisoService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'PERMISO_PERMANENTE' && $estadoFlujo === EmpleadoPermisoPermanenteService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'VACACION' && $estadoFlujo === EmpleadoVacacionService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'INCAPACIDAD' && $estadoFlujo === 'RADICADO')
            );
            $novedad->puede_rechazar_rrhh = $puedeGestionarRrhh && (
                ($tipo === 'PERMISO' && $estadoFlujo === EmpleadoPermisoService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'PERMISO_PERMANENTE' && $estadoFlujo === EmpleadoPermisoPermanenteService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'VACACION' && $estadoFlujo === EmpleadoVacacionService::ESTADO_JEFE_APROBADO)
                || ($tipo === 'INCAPACIDAD' && $estadoFlujo === 'RADICADO')
            );
            $novedad->puede_ver_gestion = $tipo === 'INCAPACIDAD' && ! empty($novedad->origen_gestion_url);

            return $novedad;
        });
        return view('gestionrrhh::novedades.novedades_lista', [
            'novedades' => $novedades,
            'filtros' => $filtros,
            'tiposDisponibles' => $this->tiposDisponibles(),
            'estadosDisponibles' => self::ESTADOS_LISTA_RRHH,
            'modoVista' => 'rrhh',
            'tituloVista' => 'Gestion central de solicitudes',
            'breadcrumbFinal' => 'Gestion central de solicitudes',
            'forzarNoAprobadas' => true,
        ]);
    }

    public function store(StoreEmpleadoPermisoRequest $request)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();
        $adjuntos = $this->documentoService->extraerAdjuntosDesdeRequest($request);

        $resultado = $this->permisoService->crearPermiso(
            payload: $payload,
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

        $resultadoAdjuntos = $this->documentoService->guardarAdjuntos(
            idNovedad: (string) ($resultado['id_novedad'] ?? ''),
            adjuntos: is_array($adjuntos) ? $adjuntos : []
        );

        $jefeDirecto = is_array($resultado['jefe_directo'] ?? null)
            ? $resultado['jefe_directo']
            : null;
        $persona = $this->permisoService->buscarPersona((string) ($payload['identificacion'] ?? ''));
        $idNovedad = (string) ($resultado['id_novedad'] ?? '');
        $destinoCorreoRadicado = $this->correoNotificacionRadicado();
        $correoEnviado = $this->notificacionService->enviarPermisoRadicado(
            resultado: $resultado,
            payload: $payload,
            persona: $persona,
            actor: $actor,
            jefeDirecto: $jefeDirecto,
            adjuntosCorreo: $this->documentoService->construirAdjuntosCorreo(
                is_array($adjuntos) ? $adjuntos : [],
                (string) ($payload['identificacion'] ?? '')
            ),
            urlGestionJefe: $this->generarUrlAccesoDirectoJefeSeguro($destinoCorreoRadicado, $jefeDirecto, $idNovedad),
            magicLinkTtlMinutos: $this->magicLinkTtlMinutes(),
            canal: 'WEB'
        );
        $descripcionAlerta = 'La solicitud quedo radicada correctamente.';
        $descripcionAlerta .= $correoEnviado
            ? '<br>Se envio notificacion al correo del jefe.'
            : '<br>No fue posible enviar la notificacion por correo al jefe.';

        if (($resultadoAdjuntos['ok'] ?? false) && ((int) ($resultadoAdjuntos['guardados'] ?? 0) > 0)) {
            $descripcionAlerta .= '<br>Adjuntos guardados: '.(int) $resultadoAdjuntos['guardados'].'.';
        }

        $alertType = 'success';
        $alertTitle = 'Permiso radicado correctamente';

        if (! ($resultadoAdjuntos['ok'] ?? true)) {
            $alertType = 'warning';
            $alertTitle = 'Permiso radicado con advertencias';
            $descripcionAlerta .= '<br><strong>Importante:</strong> el permiso quedo radicado, pero no fue posible guardar los adjuntos.';
            if (! empty($resultadoAdjuntos['message'])) {
                $descripcionAlerta .= '<br>'.e((string) $resultadoAdjuntos['message']);
            }
            if (! empty($resultadoAdjuntos['error'])) {
                $descripcionAlerta .= '<br><small>'.e((string) $resultadoAdjuntos['error']).'</small>';
            }
        }

        if ($jefeDirecto) {
            $nombreJefe = trim((string) ($jefeDirecto['nombre'] ?? ''));
            $docJefe = trim((string) ($jefeDirecto['identificacion'] ?? ''));
            $correoJefe = trim((string) ($jefeDirecto['correo'] ?? ''));

            $nombreJefe = $nombreJefe !== '' ? e($nombreJefe) : 'SIN NOMBRE';
            $docJefe = $docJefe !== '' ? e($docJefe) : 'SIN DOCUMENTO';
            $correoJefe = $correoJefe !== '' ? e($correoJefe) : 'SIN CORREO REGISTRADO';

            $descripcionAlerta .= '<br>Se enviara correo al jefe directo:';
            $descripcionAlerta .= '<br><strong>'.$nombreJefe.'</strong>';
            $descripcionAlerta .= '<br>'.$correoJefe;
        } else {
            $descripcionAlerta .= '<br>No fue posible identificar el jefe directo para notificacion.';
        }

        return redirect()
            ->route('gestionRRHH.permisos.create')
            ->with('alert', [
                'type' => $alertType,
                'title' => $alertTitle,
                'description' => $descripcionAlerta,
            ]);
    }

    public function aprobarJefe(Request $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $tipoNovedad = $this->resolverTipoNovedad($idNovedad);
        if ($tipoNovedad === 'PERMISO_PERMANENTE') {
            $resultado = $this->permisoPermanenteService->aprobarPorJefe(
                idNovedad: $idNovedad,
                documentoActor: $actor['documento'],
                esSuperAdmin: $actor['es_super_admin']
            );

            if (! ($resultado['ok'] ?? false)) {
                return $this->redirigirConAlerta(
                    type: 'error',
                    title: (string) ($resultado['message'] ?? 'No fue posible aprobar el permiso permanente.')
                );
            }

            $correoRrhhEnviado = $this->enviarCorreoNotificacionPendienteRrhhPermisoPermanente($idNovedad, $actor);
            $mensaje = (string) ($resultado['message'] ?? 'Permiso permanente aprobado por jefe.');
            $mensaje .= $correoRrhhEnviado
                ? ' Se envio notificacion al area de RRHH.'
                : ' No fue posible enviar la notificacion al area de RRHH.';

            return $this->redirigirConAlerta(
                type: 'success',
                title: $mensaje
            );
        }

        if ($tipoNovedad === 'VACACION') {
            $resultado = $this->vacacionService->aprobarPorJefe(
                idNovedad: $idNovedad,
                documentoActor: $actor['documento'],
                esSuperAdmin: $actor['es_super_admin']
            );

            if (! ($resultado['ok'] ?? false)) {
                return $this->redirigirConAlerta(
                    type: 'error',
                    title: (string) ($resultado['message'] ?? 'No fue posible aprobar vacaciones.')
                );
            }

            if (($resultado['requiere_aprobacion_rrhh'] ?? false) === true) {
                $correoRrhhEnviado = $this->enviarCorreoNotificacionPendienteRrhhVacacion($idNovedad, $actor);
                $mensaje = (string) ($resultado['message'] ?? 'Vacaciones aprobadas en nivel inicial.');
                $mensaje .= $correoRrhhEnviado
                    ? ' Se envio notificacion al area de RRHH.'
                    : ' No fue posible enviar la notificacion al area de RRHH.';

                return $this->redirigirConAlerta(type: 'success', title: $mensaje);
            }

            return $this->redirigirConAlerta(
                type: 'success',
                title: (string) ($resultado['message'] ?? 'Vacaciones aprobadas correctamente.')
            );
        }

        $resultado = $this->permisoService->aprobarPorJefe(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            esSuperAdmin: $actor['es_super_admin']
        );

        if (! ($resultado['ok'] ?? false)) {
            return $this->redirigirConAlerta(
                type: 'error',
                title: (string) ($resultado['message'] ?? 'No fue posible aprobar por jefe.')
            );
        }

        $correoRrhhEnviado = $this->notificacionService->enviarPermisoPendienteRrhh(
            idNovedad: $idNovedad,
            actor: $actor,
            urlAccion: route('gestionRRHH.permisos.rrhh'),
            canal: 'WEB'
        );

        $mensaje = (string) ($resultado['message'] ?? 'Permiso aprobado por jefe.');
        if ($correoRrhhEnviado) {
            $mensaje .= ' Se envio notificacion al area de RRHH.';
        } else {
            $mensaje .= ' No fue posible enviar la notificacion al area de RRHH.';
        }

        return $this->redirigirConAlerta(
            type: 'success',
            title: $mensaje
        );
    }

    public function aprobarRrhh(Request $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $tipoNovedad = $this->resolverTipoNovedad($idNovedad);
        if ($tipoNovedad === 'PERMISO_PERMANENTE') {
            $resultado = $this->permisoPermanenteService->aprobarPorRrhh(
                idNovedad: $idNovedad,
                documentoActor: $actor['documento'],
                esRrhh: $actor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible aprobar permiso permanente por RRHH.',
                'Permiso permanente aprobado por RRHH.'
            );
        }

        if ($tipoNovedad === 'VACACION') {
            $resultado = $this->vacacionService->aprobarPorRrhh(
                idNovedad: $idNovedad,
                documentoActor: $actor['documento'],
                esRrhh: $actor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible aprobar vacaciones por RRHH.',
                'Vacaciones aprobadas por RRHH.'
            );
        }

        $resultado = $this->permisoService->aprobarPorRrhh(
            idNovedad: $idNovedad,
            documentoActor: $actor['documento'],
            esRrhh: $actor['puede_gestionar_permisos_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible aprobar por RRHH.', 'Permiso aprobado por RRHH.');
    }

    public function rechazar(RechazarEmpleadoPermisoRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();
        $tipoNovedad = $this->resolverTipoNovedad($idNovedad);
        if ($tipoNovedad === 'PERMISO_PERMANENTE') {
            $resultado = $this->permisoPermanenteService->rechazarPermisoPermanente(
                idNovedad: $idNovedad,
                nivel: (string) $payload['nivel'],
                motivoRechazo: (string) $payload['motivo_rechazo'],
                documentoActor: $actor['documento'],
                esRrhh: $actor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible rechazar el permiso permanente.',
                'Permiso permanente rechazado correctamente.'
            );
        }

        if ($tipoNovedad === 'VACACION') {
            $resultado = $this->vacacionService->rechazarVacacion(
                idNovedad: $idNovedad,
                nivel: (string) $payload['nivel'],
                motivoRechazo: (string) $payload['motivo_rechazo'],
                documentoActor: $actor['documento'],
                esRrhh: $actor['puede_gestionar_permisos_rrhh'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible rechazar la solicitud de vacaciones.',
                'Solicitud de vacaciones rechazada correctamente.'
            );
        }

        $resultado = $this->permisoService->rechazarPermiso(
            idNovedad: $idNovedad,
            nivel: (string) $payload['nivel'],
            motivoRechazo: (string) $payload['motivo_rechazo'],
            documentoActor: $actor['documento'],
            esRrhh: $actor['puede_gestionar_permisos_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible rechazar el permiso.', 'Permiso rechazado correctamente.');
    }

    public function anular(AnularEmpleadoPermisoRequest $request, string $idNovedad)
    {
        $actor = $this->resolverContextoActor($request);
        $payload = $request->validated();
        $tipoNovedad = $this->resolverTipoNovedad($idNovedad);
        if ($tipoNovedad === 'PERMISO_PERMANENTE') {
            $resultado = $this->permisoPermanenteService->anularPermisoPermanente(
                idNovedad: $idNovedad,
                motivoAnulacion: (string) $payload['motivo_anulacion'],
                documentoActor: $actor['documento'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible anular el permiso permanente.',
                'Permiso permanente anulado correctamente.'
            );
        }

        if ($tipoNovedad === 'VACACION') {
            $resultado = $this->vacacionService->anularVacacion(
                idNovedad: $idNovedad,
                motivoAnulacion: (string) $payload['motivo_anulacion'],
                documentoActor: $actor['documento'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible anular la solicitud de vacaciones.',
                'Solicitud de vacaciones anulada correctamente.'
            );
        }

        if ($tipoNovedad === 'INCAPACIDAD') {
            $resultado = $this->incapacidadService->anularSolicitud(
                idNovedad: $idNovedad,
                documentoActor: $actor['documento'],
                motivoAnulacion: (string) $payload['motivo_anulacion'],
                esRrhh: $actor['es_rrhh'],
                esSuperAdmin: $actor['es_super_admin']
            );

            return $this->redirigirConResultado(
                $resultado,
                'No fue posible anular la solicitud de incapacidad.',
                'Solicitud de incapacidad anulada correctamente.'
            );
        }

        $resultado = $this->permisoService->anularPermiso(
            idNovedad: $idNovedad,
            motivoAnulacion: (string) $payload['motivo_anulacion'],
            documentoActor: $actor['documento'],
            esRrhh: $actor['es_rrhh'],
            esSuperAdmin: $actor['es_super_admin']
        );

        return $this->redirigirConResultado($resultado, 'No fue posible anular el permiso.', 'Permiso anulado correctamente.');
    }

    public function seguimiento(Request $request, string $idNovedad)
    {
        $seguimiento = $this->permisoService->obtenerSeguimientoPermiso($idNovedad);
        if (! $seguimiento) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontro el permiso.',
            ], 404);
        }

        if (! $this->permisoService->usuarioPuedeConsultarPermiso($request->user(), $idNovedad)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes autorizacion para consultar el seguimiento de este permiso.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'data' => $seguimiento,
        ]);
    }

    public function pdf(Request $request, string $idNovedad)
    {
        if (! $this->permisoService->usuarioPuedeConsultarPermiso($request->user(), $idNovedad)) {
            return redirect()->back()->with('error', 'No tienes autorizacion para consultar el PDF de este permiso.');
        }

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

    public function documentos(Request $request, string $idNovedad)
    {
        if (! $this->novedadService->usuarioPuedeConsultarNovedad($request->user(), $idNovedad)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes autorizacion para consultar los adjuntos de esta novedad.',
                ], 403);
            }

            return redirect()->back()->with('error', 'No tienes autorizacion para consultar los adjuntos de esta novedad.');
        }

        $adjuntos = $this->documentoService->obtenerAdjuntosPorNovedad($idNovedad);

        if ($request->expectsJson() || $request->ajax()) {
            $data = collect($adjuntos)->map(function ($adjunto) {
                $idDocumento = trim((string) ($adjunto->id ?? ''));
                $rutaDocumento = trim((string) ($adjunto->ruta_documento ?? ''));

                return [
                    'id_documento' => $idDocumento,
                    'tipo_documento' => trim((string) ($adjunto->tipo_documento ?? '')),
                    'ruta_documento' => $rutaDocumento,
                    'fecha_creacion' => trim((string) ($adjunto->fecha_creacion ?? '')),
                    'nombre_archivo' => $rutaDocumento !== '' ? basename($rutaDocumento) : '',
                    'ver_documento_url' => $idDocumento !== '' ? route('gestionRRHH.permisos.documentos.ver', ['idDocumento' => $idDocumento]) : null,
                ];
            })->values();

            return response()->json([
                'ok' => true,
                'id_novedad' => $idNovedad,
                'adjuntos' => $data,
            ]);
        }

        return view('gestionrrhh::novedades.documentos', [
            'idNovedad' => $idNovedad,
            'adjuntos' => $adjuntos,
        ]);
    }

    public function verDocumento(Request $request, string $idDocumento)
    {
        $documento = $this->documentoService->obtenerAdjuntoPorId($idDocumento);
        if (! $documento) {
            return redirect()->back()->with('error', 'No se encontro el adjunto solicitado.');
        }

        if (! $this->novedadService->usuarioPuedeConsultarNovedad($request->user(), (string) $documento->id_novedad)) {
            return redirect()->back()->with('error', 'No tienes autorizacion para consultar este adjunto.');
        }

        try {
            $rutaDocumento = trim((string) ($documento->ruta_documento ?? ''));
            $stream = $this->documentalStorage->abrirStream($rutaDocumento);
            $contentType = $this->documentalStorage->obtenerMimeType($rutaDocumento);
            $filename = $this->documentalStorage->obtenerNombreArchivo($rutaDocumento);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error sirviendo adjunto de permiso', [
                'id_documento' => $idDocumento,
                'ruta' => (string) ($documento->ruta_documento ?? ''),
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('error', 'No fue posible abrir el adjunto del permiso.');
    }

    private function resolverContextoActor(Request $request): array
    {
        $usuario = $request->user();
        $documento = $this->permisoService->obtenerDocumentoUsuario($usuario);
        $esJefe = $this->permisoService->esUsuarioJefe($usuario);
        $esAsociado = EmpleadoService::esAsociadoActivo($documento);

        return [
            'documento' => $documento,
            'nombre' => $this->permisoService->obtenerNombreUsuario($usuario),
            'es_rrhh' => $this->permisoService->esUsuarioRrhh($usuario),
            'es_super_admin' => $this->permisoService->esUsuarioSuperAdmin($usuario),
            'es_jefe' => $esJefe,
            'es_asociado' => $esAsociado,
            'puede_gestionar_solicitudes_equipo' => $esJefe || $esAsociado,
            'puede_gestionar_permisos_rrhh' => $this->puedeGestionarPermisosRrhh($usuario),
        ];
    }

    private function puedeGestionarPermisosRrhh(?User $usuario): bool
    {
        if (! $usuario) {
            return false;
        }

        return $usuario->can(Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH);
    }

    private function puedeVerNovedades(?User $usuario): bool
    {
        if (! $usuario) {
            return false;
        }

        return $usuario->can(Permisos::GESTION_RRHH_PERMISOS_NOVEDADES);
    }

    private function redirigirConResultado(array $resultado, string $errorFallback, string $successFallback)
    {
        if (! ($resultado['ok'] ?? false)) {
            return $this->redirigirConAlerta(
                type: 'error',
                title: (string) ($resultado['message'] ?? $errorFallback)
            );
        }

        return $this->redirigirConAlerta(
            type: 'success',
            title: (string) ($resultado['message'] ?? $successFallback)
        );
    }

    private function redirigirConAlerta(string $type, string $title)
    {
        return redirect()->back()->with('alert', [
            'type' => $type,
            'title' => $title,
        ]);
    }

    private function tiposDisponibles(): array
    {
        return [
            'PERMISO' => 'Permiso',
            'PERMISO_PERMANENTE' => 'Permiso permanente',
            'INCAPACIDAD' => 'Incapacidad',
            'VACACION' => 'Vacacion',
        ];
    }

    private function resolverTipoNovedad(string $idNovedad): string
    {
        $novedad = $this->novedadService->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return '';
        }

        return NovedadTipoResolver::normalizar(
            (string) ($novedad->tipo_tabla_db ?? ''),
            (string) ($novedad->tipo_descripcion ?? '')
        );
    }

    private function enviarCorreoNotificacionPendienteRrhhVacacion(string $idNovedad, array $actor): bool
    {
        return $this->notificacionService->enviarPendienteRrhhVacacion(
            idNovedad: $idNovedad,
            actor: $actor,
            urlAccion: route('gestionRRHH.permisos.rrhh'),
            canal: 'WEB'
        );
    }

    private function enviarCorreoNotificacionPendienteRrhhPermisoPermanente(string $idNovedad, array $actor): bool
    {
        return $this->notificacionService->enviarPendienteRrhhPermisoPermanente(
            idNovedad: $idNovedad,
            actor: $actor,
            urlAccion: route('gestionRRHH.permisos.rrhh'),
            canal: 'WEB'
        );
    }

    private function formatearFechaCorreo(mixed $valor, string $formato): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '';
        }

        try {
            return Carbon::parse($texto)->format($formato);
        } catch (\Throwable) {
            return $texto;
        }
    }

    private function generarUrlAccesoDirectoJefe(?array $jefeDirecto, string $idNovedad): ?string
    {
        $documentoJefe = trim((string) ($jefeDirecto['identificacion'] ?? ''));
        if ($documentoJefe === '') {
            return null;
        }

        $usuarioJefe = $this->actorNovedadService->buscarUsuarioActivoPorDocumento($documentoJefe);
        if (! $usuarioJefe) {
            return null;
        }

        if (! $this->permisoService->esUsuarioJefe($usuarioJefe) && ! $this->permisoService->esUsuarioSuperAdmin($usuarioJefe)) {
            return null;
        }

        $token = (string) Str::uuid();
        Cache::put(self::MAGIC_LINK_CACHE_PREFIX.$token, [
            'documento_jefe' => $documentoJefe,
            'id_usuario' => (int) $usuarioJefe->IdUsuario,
            'id_novedad' => trim($idNovedad),
            'creado_en' => now()->toDateTimeString(),
        ], now()->addMinutes($this->magicLinkTtlMinutes()));

        return URL::temporarySignedRoute(
            'gestionRRHH.permisos.jefe.magic',
            now()->addMinutes($this->magicLinkTtlMinutes()),
            ['token' => $token]
        );
    }

    private function generarUrlAccesoDirectoJefeSeguro(string $destino, ?array $jefeDirecto, string $idNovedad): ?string
    {
        if ($this->debeForzarMagicLinkEnPruebas()) {
            return $this->generarUrlAccesoDirectoJefe($jefeDirecto, $idNovedad);
        }

        $correoDestino = strtolower(trim($destino));
        $correoJefe = strtolower(trim((string) ($jefeDirecto['correo'] ?? '')));
        if ($correoDestino === '' || $correoJefe === '' || $correoDestino !== $correoJefe) {
            return null;
        }

        return $this->generarUrlAccesoDirectoJefe($jefeDirecto, $idNovedad);
    }

    private function debeForzarMagicLinkEnPruebas(): bool
    {
        if ((bool) config('services.employee_permits.notifications.force_magic_link_for_testing', false)) {
            return true;
        }

        $env = strtolower(trim((string) config('app.env', '')));

        return in_array($env, ['local', 'development', 'testing'], true);
    }

    private function correoNotificacionRadicado(): string
    {
        return trim((string) config('services.employee_permits.notifications.radicado_email', 'desarrollo3@copetran.com'));
    }

    private function magicLinkTtlMinutes(): int
    {
        $ttl = (int) config('services.employee_permits.notifications.magic_link_ttl_minutes', 20);

        if ($ttl < 5) {
            return 5;
        }

        if ($ttl > 240) {
            return 240;
        }

        return $ttl;
    }

    private function formatearFechaSolo(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : 'N/A';
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function formatearHora12DesdeValor(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('h:i A') : 'N/A';
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function formatearHora24DesdeValor(mixed $fecha): string
    {
        try {
            return $fecha ? Carbon::parse($fecha)->format('H:i') : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
