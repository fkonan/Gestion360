<?php

namespace App\Modules\GestionRRHH\Services\Novedades;

use App\Modules\GestionRRHH\Jobs\EnviarNotificacionNovedadJob;
use App\Modules\GestionRRHH\Mail\Descargos\NuevaCitacionDescargosMail;
use App\Modules\GestionRRHH\Mail\Incapacidades\NuevaIncapacidadRadicadaMail;
use App\Modules\GestionRRHH\Mail\Permisos\NuevoPermisoRadicadoMail;
use App\Modules\GestionRRHH\Mail\PermisosPermanentes\NuevoPermisoPermanenteRadicadoMail;
use App\Modules\GestionRRHH\Mail\Vacaciones\NuevaVacacionRadicadaMail;
use App\Modules\GestionRRHH\Services\Novedades\Descargos\DescargoCitacionService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Incapacidades\EmpleadoIncapacidadService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoPdfService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class NovedadNotificacionService
{
    public const TIPO_PERMISO_RADICADO = 'permiso_radicado';
    public const TIPO_PERMISO_PENDIENTE_RRHH = 'permiso_pendiente_rrhh';
    public const TIPO_VACACION_RADICADA = 'vacacion_radicada';
    public const TIPO_PERMISO_PERMANENTE_RADICADO = 'permiso_permanente_radicado';
    public const TIPO_INCAPACIDAD_RADICADA_RRHH = 'incapacidad_radicada_rrhh';
    public const TIPO_CITACION_DESCARGOS_EMPLEADO = 'citacion_descargos_empleado';
    public const TIPO_VACACION_PENDIENTE_RRHH = 'vacacion_pendiente_rrhh';
    public const TIPO_PERMISO_PERMANENTE_PENDIENTE_RRHH = 'permiso_permanente_pendiente_rrhh';

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly EmpleadoPermisoPdfService $permisoPdfService,
        private readonly EmpleadoPermisoDocumentoService $documentoService,
        private readonly ActorNovedadService $actorNovedadService,
        private readonly EmpleadoVacacionService $vacacionService,
        private readonly EmpleadoVacacionDocumentoService $vacacionDocumentoService,
        private readonly EmpleadoPermisoPermanenteService $permisoPermanenteService,
        private readonly EmpleadoPermisoPermanenteDocumentoService $permisoPermanenteDocumentoService,
        private readonly EmpleadoIncapacidadService $incapacidadService,
        private readonly EmpleadoIncapacidadDocumentoService $incapacidadDocumentoService,
        private readonly DescargoCitacionService $descargoCitacionService,
    ) {}

    public function procesarNotificacionGenerica(string $tipo, array $payload): void
    {
        $tipo = trim($tipo);

        match ($tipo) {
            self::TIPO_PERMISO_RADICADO => $this->procesarPermisoRadicado(
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                persona: is_array($payload['persona'] ?? null) ? $payload['persona'] : null,
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                jefeDirecto: is_array($payload['jefe_directo'] ?? null) ? $payload['jefe_directo'] : null,
                urlGestionJefe: isset($payload['url_gestion_jefe']) ? (string) $payload['url_gestion_jefe'] : null,
                magicLinkTtlMinutos: isset($payload['magic_link_ttl_minutos']) ? (int) $payload['magic_link_ttl_minutos'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB')
            ),
            self::TIPO_PERMISO_PENDIENTE_RRHH => $this->procesarPermisoPendienteRrhh(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB')
            ),
            self::TIPO_VACACION_RADICADA => $this->enviarVacacionRadicada(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            self::TIPO_PERMISO_PERMANENTE_RADICADO => $this->enviarPermisoPermanenteRadicado(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            self::TIPO_INCAPACIDAD_RADICADA_RRHH => $this->enviarIncapacidadRadicadaRrhh(
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            self::TIPO_CITACION_DESCARGOS_EMPLEADO => $this->enviarCitacionDescargosEmpleado(
                idCitacion: (string) ($payload['id_citacion'] ?? ''),
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                persona: is_array($payload['persona'] ?? null) ? $payload['persona'] : [],
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            self::TIPO_VACACION_PENDIENTE_RRHH => $this->enviarPendienteRrhhVacacion(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            self::TIPO_PERMISO_PERMANENTE_PENDIENTE_RRHH => $this->enviarPendienteRrhhPermisoPermanente(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            default => Log::warning('Tipo de notificacion RRHH no soportado', ['tipo' => $tipo]),
        };
    }

    private function encolarNotificacionGenerica(string $tipo, array $payload, string $canal, array $contexto = []): bool
    {
        try {
            $payload['canal'] = strtoupper(trim($canal)) !== '' ? strtoupper(trim($canal)) : 'WEB';
            EnviarNotificacionNovedadJob::dispatch($tipo, $payload);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error encolando notificacion RRHH', array_merge($contexto, [
                'tipo' => $tipo,
                'canal' => strtoupper(trim($canal)),
                'message' => $e->getMessage(),
            ]));

            return false;
        }
    }

    public function enviarPermisoRadicado(
        array $resultado,
        array $payload,
        ?array $persona,
        array $actor,
        ?array $jefeDirecto,
        array $adjuntosCorreo = [],
        ?string $urlGestionJefe = null,
        ?int $magicLinkTtlMinutos = null,
        string $canal = 'WEB'
    ): bool {
        return $this->encolarNotificacionGenerica(
            tipo: self::TIPO_PERMISO_RADICADO,
            payload: [
                'resultado' => $resultado,
                'payload' => $this->extraerPayloadCorreoPermiso($payload),
                'persona' => $persona,
                'actor' => $actor,
                'jefe_directo' => $jefeDirecto,
                'url_gestion_jefe' => $urlGestionJefe,
                'magic_link_ttl_minutos' => $magicLinkTtlMinutos,
            ],
            canal: $canal,
            contexto: [
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
            ]
        );
    }

    public function procesarPermisoRadicado(
        array $resultado,
        array $payload,
        ?array $persona,
        array $actor,
        ?array $jefeDirecto,
        ?string $urlGestionJefe = null,
        ?int $magicLinkTtlMinutos = null,
        string $canal = 'WEB'
    ): bool {
        try {
            $correoJefe = trim((string) ($jefeDirecto['correo'] ?? ''));
            $destino = $this->resolverDestinoNotificacionGestionInicial($correoJefe);
            if ($destino === '') {
                Log::warning('No se envio correo de permiso radicado: destino no configurado.', [
                    'canal' => strtoupper(trim($canal)),
                    'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                ]);

                return false;
            }

            $identificacion = trim((string) ($payload['identificacion'] ?? ''));
            if ($identificacion !== '' && (! is_array($persona) || trim((string) ($persona['nombre'] ?? '')) === '')) {
                $persona = $this->permisoService->buscarPersona($identificacion);
            }

            $datos = [
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'fecha_permiso' => (string) ($payload['fecha_permiso'] ?? ''),
                'hora_salida' => $this->formatearHoraCorreoDesdeTexto((string) ($payload['hora_salida'] ?? '')),
                'hora_ingreso' => $this->formatearHoraCorreoDesdeTexto(trim((string) ($payload['hora_ingreso'] ?? ''))),
                'motivo' => $this->resolverMotivoLabel($payload),
                'actividad' => trim((string) ($payload['actividad'] ?? '')),
                'empleado_documento' => $identificacion,
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => trim((string) ($actor['documento'] ?? '')),
                'radicado_por_nombre' => trim((string) ($actor['nombre'] ?? '')),
                'jefe_nombre' => trim((string) ($jefeDirecto['nombre'] ?? '')),
                'jefe_documento' => trim((string) ($jefeDirecto['identificacion'] ?? '')),
                'jefe_correo' => trim((string) ($jefeDirecto['correo'] ?? '')),
                'url_login' => $this->obtenerUrlLoginSistema(),
                'texto_login' => 'Ir al login',
                'nota_login' => 'Si no tienes acceso directo o no tienes usuario en Autogestion, inicia sesion con tus credenciales del sistema Logtrans.',
                'fecha_radicado' => now()->format('Y-m-d H:i:s'),
            ];

            $urlGestionJefe = trim((string) $urlGestionJefe);
            if ($urlGestionJefe !== '') {
                $datos['url_gestion_jefe'] = $urlGestionJefe;
            }

            if (is_int($magicLinkTtlMinutos) && $magicLinkTtlMinutos > 0) {
                $datos['magic_link_ttl_minutos'] = $magicLinkTtlMinutos;
            }

            $adjuntoPdf = strtoupper(trim($canal)) === 'API'
                ? null
                : $this->construirAdjuntoPdfPermisoRadicado((string) ($resultado['id_novedad'] ?? ''));

            $adjuntosCorreo = $this->documentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: (string) ($resultado['id_novedad'] ?? ''),
                identificacion: $identificacion
            );

            $mail = new NuevoPermisoRadicadoMail(
                datos: $datos,
                pdfContenidoBase64: isset($adjuntoPdf['contenido']) && is_string($adjuntoPdf['contenido']) && $adjuntoPdf['contenido'] !== ''
                    ? base64_encode($adjuntoPdf['contenido'])
                    : null,
                pdfNombre: $adjuntoPdf['nombre'] ?? null,
                adjuntos: $adjuntosCorreo
            );

            // Evita re-encolar payloads grandes: este envio ya corre dentro del worker.
            $mail->onConnection('sync');
            $mail->onQueue('default');

            Mail::to($destino)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de permiso radicado', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarPermisoPendienteRrhh(
        string $idNovedad,
        array $actor,
        ?string $urlAccion = null,
        string $canal = 'WEB'
    ): bool {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return false;
        }

        return $this->encolarNotificacionGenerica(
            tipo: self::TIPO_PERMISO_PENDIENTE_RRHH,
            payload: [
                'id_novedad' => $idNovedad,
                'actor' => $actor,
                'url_accion' => $urlAccion,
            ],
            canal: $canal,
            contexto: [
                'id_novedad' => $idNovedad,
            ]
        );
    }

    public function procesarPermisoPendienteRrhh(
        string $idNovedad,
        array $actor,
        ?string $urlAccion = null,
        string $canal = 'WEB'
    ): bool {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return false;
        }

        try {
            $destino = $this->correoNotificacionRrhh();
            if ($destino === '') {
                Log::warning('No se envio correo de permiso pendiente RRHH: destino no configurado.', [
                    'canal' => strtoupper(trim($canal)),
                    'id_novedad' => $idNovedad,
                ]);

                return false;
            }

            $detalle = $this->permisoService->obtenerDetalleParaPdf($idNovedad);
            $datos = $this->construirDatosCorreoPendienteRrhh($detalle, $actor, $urlAccion);
            $adjuntoPdf = $this->construirAdjuntoPdfPermisoRadicado($idNovedad);
            $adjuntosCorreo = $this->documentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: trim((string) ($datos['empleado_documento'] ?? ''))
            );

            $mail = new NuevoPermisoRadicadoMail(
                datos: $datos,
                pdfContenidoBase64: isset($adjuntoPdf['contenido']) && is_string($adjuntoPdf['contenido']) && $adjuntoPdf['contenido'] !== ''
                    ? base64_encode($adjuntoPdf['contenido'])
                    : null,
                pdfNombre: $adjuntoPdf['nombre'] ?? null,
                adjuntos: $adjuntosCorreo,
                asunto: 'Permiso pendiente de aprobacion RRHH'
            );

            // Evita re-encolar un payload pesado: este envio ya corre dentro del worker.
            $mail->onConnection('sync');
            $mail->onQueue('default');

            Mail::to($destino)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de permiso pendiente RRHH', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => $idNovedad,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarVacacionRadicada(
        string $idNovedad,
        array $resultado,
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        ?string $urlAccion = null,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_VACACION_RADICADA,
                payload: [
                    'id_novedad' => $idNovedad,
                    'resultado' => $resultado,
                    'payload' => [
                        'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
                        'fecha_inicio' => trim((string) ($payload['fecha_inicio'] ?? '')),
                        'fecha_fin' => trim((string) ($payload['fecha_fin'] ?? '')),
                        'observacion' => trim((string) ($payload['observacion'] ?? '')),
                    ],
                    'documento_actor' => $documentoActor,
                    'nombre_actor' => $nombreActor,
                    'url_accion' => $urlAccion,
                ],
                canal: $canal,
                contexto: ['id_novedad' => $idNovedad]
            );
        }

        try {
            $detalle = $this->vacacionService->obtenerDetalleCorreo($idNovedad);
            $persona = is_array($detalle['persona'] ?? null) ? $detalle['persona'] : [];
            $vacacion = is_object($detalle['vacacion'] ?? null) ? $detalle['vacacion'] : null;
            $aprobador = is_array($resultado['aprobador'] ?? null) ? $resultado['aprobador'] : [];
            $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
            $correoAprobador = trim((string) ($aprobador['correo'] ?? ''));
            if ($correoAprobador === '') {
                $correoAprobador = trim((string) ($vacacion->correo_aprobador ?? ''));
            }
            $destino = $this->resolverDestinoNotificacionGestionInicial($correoAprobador);
            if ($destino === '') {
                return false;
            }

            $datos = [
                'titulo_correo' => 'Nueva solicitud de vacaciones',
                'mensaje_correo' => 'Se registro una solicitud de vacaciones pendiente de tu gestion.',
                'id_novedad' => $idNovedad,
                'empleado_documento' => $documentoPersona,
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => trim((string) $documentoActor),
                'radicado_por_nombre' => trim((string) $nombreActor),
                'fecha_inicio' => $this->formatearFechaCorreo((string) ($payload['fecha_inicio'] ?? ''), 'Y-m-d'),
                'fecha_fin' => $this->formatearFechaCorreo((string) ($payload['fecha_fin'] ?? ''), 'Y-m-d'),
                'observacion' => trim((string) ($payload['observacion'] ?? '')),
                'aprobador_real_nombre' => trim((string) ($aprobador['nombre'] ?? '')),
                'aprobador_real_documento' => trim((string) ($aprobador['documento'] ?? '')),
                'aprobador_real_correo' => $correoAprobador,
                'url_accion' => trim((string) $urlAccion) !== '' ? trim((string) $urlAccion) : $this->obtenerUrlGestionJefe(),
                'texto_accion' => 'Ir a solicitudes del equipo',
                'fecha_radicado' => $this->formatearFechaCorreo((string) ($vacacion->fecha_creacion ?? ''), 'Y-m-d H:i:s'),
            ];

            $adjuntosCorreo = $this->vacacionDocumentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: $documentoPersona
            );

            Mail::to($destino)->queue(new NuevaVacacionRadicadaMail(
                datos: $datos,
                adjuntos: $adjuntosCorreo
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de vacaciones radicadas', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => $idNovedad,
                'destino' => $destino,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarPermisoPermanenteRadicado(
        string $idNovedad,
        array $resultado,
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        ?string $urlAccion = null,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_PERMISO_PERMANENTE_RADICADO,
                payload: [
                    'id_novedad' => $idNovedad,
                    'resultado' => $resultado,
                    'payload' => [
                        'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
                        'fecha_inicio' => trim((string) ($payload['fecha_inicio'] ?? '')),
                        'fecha_fin' => trim((string) ($payload['fecha_fin'] ?? '')),
                        'jornada' => trim((string) ($payload['jornada'] ?? '')),
                        'horario_fijo' => filter_var($payload['horario_fijo'] ?? false, FILTER_VALIDATE_BOOL),
                        'observacion' => trim((string) ($payload['observacion'] ?? '')),
                    ],
                    'documento_actor' => $documentoActor,
                    'nombre_actor' => $nombreActor,
                    'url_accion' => $urlAccion,
                ],
                canal: $canal,
                contexto: ['id_novedad' => $idNovedad]
            );
        }

        try {
            $detalle = $this->permisoPermanenteService->obtenerDetalleCorreo($idNovedad);
            $persona = is_array($detalle['persona'] ?? null) ? $detalle['persona'] : [];
            $permiso = is_object($detalle['permiso'] ?? null) ? $detalle['permiso'] : null;
            $aprobador = is_array($resultado['jefe_directo'] ?? null)
                ? $resultado['jefe_directo']
                : (is_array($resultado['aprobador'] ?? null) ? $resultado['aprobador'] : []);
            $documentoPersona = trim((string) ($payload['identificacion'] ?? ''));
            $correoAprobador = trim((string) ($aprobador['correo'] ?? ''));
            if ($correoAprobador === '') {
                $correoAprobador = trim((string) ($permiso->correo_aprobador ?? ''));
            }
            $destino = $this->resolverDestinoNotificacionGestionInicial($correoAprobador);
            if ($destino === '') {
                return false;
            }

            $datos = [
                'titulo_correo' => 'Nuevo permiso permanente radicado',
                'mensaje_correo' => 'Se registro una solicitud de permiso permanente pendiente de gestion.',
                'id_novedad' => $idNovedad,
                'empleado_documento' => $documentoPersona,
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => trim((string) $documentoActor),
                'radicado_por_nombre' => trim((string) $nombreActor),
                'fecha_inicio' => $this->formatearFechaCorreo((string) ($payload['fecha_inicio'] ?? ''), 'Y-m-d'),
                'fecha_fin' => $this->formatearFechaCorreo((string) ($payload['fecha_fin'] ?? ''), 'Y-m-d'),
                'jornada' => EmpleadoPermisoPermanenteService::opcionesJornada()[
                    EmpleadoPermisoPermanenteService::normalizarJornada((string) ($payload['jornada'] ?? '')) ?? ''
                ] ?? trim((string) ($payload['jornada'] ?? '')),
                'horario_fijo' => filter_var($payload['horario_fijo'] ?? false, FILTER_VALIDATE_BOOL) ? 'SI' : 'NO',
                'observacion' => trim((string) ($payload['observacion'] ?? '')),
                'aprobador_real_nombre' => trim((string) ($aprobador['nombre'] ?? '')),
                'aprobador_real_documento' => trim((string) ($aprobador['identificacion'] ?? $aprobador['documento'] ?? '')),
                'aprobador_real_correo' => $correoAprobador,
                'url_accion' => trim((string) $urlAccion) !== '' ? trim((string) $urlAccion) : $this->obtenerUrlGestionJefe(),
                'texto_accion' => 'Ir a solicitudes del equipo',
                'fecha_radicado' => $this->formatearFechaCorreo((string) ($permiso->fecha_creacion ?? ''), 'Y-m-d H:i:s'),
            ];

            $adjuntosCorreo = $this->permisoPermanenteDocumentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: $documentoPersona
            );

            Mail::to($destino)->queue(new NuevoPermisoPermanenteRadicadoMail(
                datos: $datos,
                adjuntos: $adjuntosCorreo
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de permiso permanente radicado', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => $idNovedad,
                'destino' => $destino,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarIncapacidadRadicadaRrhh(
        array $resultado,
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        ?string $urlAccion = null,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_INCAPACIDAD_RADICADA_RRHH,
                payload: [
                    'resultado' => $resultado,
                    'payload' => [
                        'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
                        'causa_id' => trim((string) ($payload['causa_id'] ?? '')),
                        'diagnostico_id' => trim((string) ($payload['diagnostico_id'] ?? '')),
                        'eps_id' => trim((string) ($payload['eps_id'] ?? '')),
                        'arl_id' => trim((string) ($payload['arl_id'] ?? '')),
                        'tipo_incapacidad' => trim((string) ($payload['tipo_incapacidad'] ?? '')),
                        'fecha_inicio' => trim((string) ($payload['fecha_inicio'] ?? '')),
                        'fecha_fin' => trim((string) ($payload['fecha_fin'] ?? '')),
                        'observacion' => trim((string) ($payload['observacion'] ?? '')),
                    ],
                    'documento_actor' => $documentoActor,
                    'nombre_actor' => $nombreActor,
                    'url_accion' => $urlAccion,
                ],
                canal: $canal,
                contexto: ['id_novedad' => (string) ($resultado['id_novedad'] ?? '')]
            );
        }

        $destino = $this->correoNotificacionRrhh();
        if ($destino === '') {
            return false;
        }

        try {
            $persona = $this->incapacidadService->buscarPersona((string) ($payload['identificacion'] ?? ''));
            $causa = $this->incapacidadService->obtenerCausas()[(string) ($payload['causa_id'] ?? '')] ?? '';
            $eps = $this->incapacidadService->obtenerEps()[(string) ($payload['eps_id'] ?? '')] ?? '';
            $arl = $this->incapacidadService->obtenerArl()[(string) ($payload['arl_id'] ?? '')] ?? '';
            $tipoIncapacidad = EmpleadoIncapacidadService::opcionesTipoIncapacidad()[(string) ($payload['tipo_incapacidad'] ?? '')] ?? (string) ($payload['tipo_incapacidad'] ?? '');
            $diagnostico = $this->incapacidadService->obtenerDiagnosticoPorId((string) ($payload['diagnostico_id'] ?? ''));
            $idNovedad = (string) ($resultado['id_novedad'] ?? '');

            $datos = [
                'titulo_correo' => 'Nueva incapacidad radicada',
                'mensaje_correo' => 'Se registro una incapacidad y queda pendiente de gestion por RRHH.',
                'url_accion' => trim((string) $urlAccion) !== '' ? trim((string) $urlAccion) : $this->obtenerUrlGestionRrhh(),
                'texto_accion' => 'Ir a gestion RRHH',
                'id_radicado' => (string) ($resultado['id_detalle'] ?? ''),
                'empleado_documento' => trim((string) ($payload['identificacion'] ?? '')),
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => trim((string) $documentoActor),
                'radicado_por_nombre' => trim((string) $nombreActor),
                'tipo_incapacidad' => trim((string) $tipoIncapacidad),
                'causa' => trim((string) $causa),
                'diagnostico' => trim((string) ($diagnostico['text'] ?? '')),
                'eps' => trim((string) $eps),
                'arl' => trim((string) $arl),
                'fecha_inicio' => $this->formatearFechaCorreo((string) ($payload['fecha_inicio'] ?? ''), 'Y-m-d'),
                'fecha_fin' => $this->formatearFechaCorreo((string) ($payload['fecha_fin'] ?? ''), 'Y-m-d'),
                'observacion' => trim((string) ($payload['observacion'] ?? '')),
                'fecha_radicado' => now()->format('Y-m-d H:i:s'),
            ];

            $adjuntosCorreo = $this->incapacidadDocumentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: trim((string) ($payload['identificacion'] ?? ''))
            );

            Mail::to($destino)->queue(new NuevaIncapacidadRadicadaMail(
                datos: $datos,
                adjuntos: $adjuntosCorreo
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de incapacidad radicada a RRHH', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => (string) ($resultado['id_novedad'] ?? ''),
                'destino' => $destino,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarCitacionDescargosEmpleado(
        string $idCitacion,
        array $payload,
        array $persona,
        array $actor,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_CITACION_DESCARGOS_EMPLEADO,
                payload: [
                    'id_citacion' => $idCitacion,
                    'payload' => $payload,
                    'persona' => $persona,
                    'actor' => $actor,
                ],
                canal: $canal,
                contexto: ['id_citacion' => $idCitacion]
            );
        }

        $documentoPersona = trim((string) ($payload['documento_persona'] ?? ''));
        if ($documentoPersona === '') {
            return false;
        }

        $correoDestino = $this->actorNovedadService->obtenerCorreoUsuarioPorDocumento($documentoPersona);
        if ($correoDestino === '') {
            Log::warning('Citación a descargos sin correo destino para empleado', [
                'id_citacion' => $idCitacion,
                'documento_persona' => $documentoPersona,
                'canal' => strtoupper(trim($canal)),
            ]);

            return false;
        }

        $fechaCitacionRaw = trim((string) ($payload['fecha_citacion'] ?? ''));
        $fechaCitacion = $this->formatearFechaCorreo($fechaCitacionRaw, 'Y-m-d H:i:s');
        if ($fechaCitacion === '') {
            $fechaCitacion = $fechaCitacionRaw;
        }

        $datos = [
            'id_citacion' => $idCitacion,
            'empleado_documento' => $documentoPersona,
            'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
            'radicado_por_documento' => trim((string) ($actor['documento'] ?? '')),
            'radicado_por_nombre' => trim((string) ($actor['nombre'] ?? '')),
            'fecha_citacion' => $fechaCitacion,
            'observacion' => trim((string) ($payload['observacion'] ?? '')) !== ''
                ? trim((string) ($payload['observacion'] ?? ''))
                : 'N/A',
            'fecha_registro' => now()->format('Y-m-d H:i:s'),
        ];

        try {
            Mail::to($correoDestino)->queue(new NuevaCitacionDescargosMail($datos));
            $this->descargoCitacionService->marcarFechaNotificacion($idCitacion);

            return true;
        } catch (\Throwable $e) {
            Log::error('Error encolando correo de citacion a descargos', [
                'id_citacion' => $idCitacion,
                'documento_persona' => $documentoPersona,
                'correo' => $correoDestino,
                'canal' => strtoupper(trim($canal)),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarPendienteRrhhVacacion(
        string $idNovedad,
        array $actor,
        ?string $urlAccion = null,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_VACACION_PENDIENTE_RRHH,
                payload: [
                    'id_novedad' => $idNovedad,
                    'actor' => $actor,
                    'url_accion' => $urlAccion,
                ],
                canal: $canal,
                contexto: ['id_novedad' => $idNovedad]
            );
        }

        $destino = $this->correoNotificacionRrhh();
        if ($destino === '') {
            return false;
        }

        try {
            $detalle = $this->vacacionService->obtenerDetalleCorreo($idNovedad);
            $persona = is_array($detalle['persona'] ?? null) ? $detalle['persona'] : [];
            $vacacion = is_object($detalle['vacacion'] ?? null) ? $detalle['vacacion'] : null;
            if (! $vacacion) {
                return false;
            }

            $documentoRadica = trim((string) ($vacacion->documento_radica ?? ''));
            $radicadoPorNombre = '';
            if ($documentoRadica !== '') {
                $personaRadica = $this->permisoService->buscarPersona($documentoRadica);
                if (is_array($personaRadica)) {
                    $radicadoPorNombre = trim((string) ($personaRadica['nombre'] ?? ''));
                }
            }

            $documentoAprobador = trim((string) ($actor['documento'] ?? ''));
            if ($documentoAprobador === '') {
                $documentoAprobador = trim((string) ($vacacion->documento_aprobador ?? ''));
            }
            $nombreAprobador = trim((string) ($actor['nombre'] ?? ''));
            if ($nombreAprobador === '') {
                $nombreAprobador = trim((string) ($vacacion->nombre_aprobador ?? ''));
            }

            $correoAprobador = $documentoAprobador !== ''
                ? $this->actorNovedadService->obtenerCorreoUsuarioPorDocumento($documentoAprobador)
                : '';
            if ($correoAprobador === '') {
                $correoAprobador = trim((string) ($vacacion->correo_aprobador ?? ''));
            }

            $documentoPersona = trim((string) ($persona['identificacion'] ?? $vacacion->documento_persona ?? ''));
            $datos = [
                'titulo_correo' => 'Vacaciones pendientes de aprobacion RRHH',
                'mensaje_correo' => 'Una solicitud de vacaciones ya fue aprobada en nivel inicial y requiere gestion de RRHH.',
                'id_novedad' => $idNovedad,
                'empleado_documento' => $documentoPersona,
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => $documentoRadica,
                'radicado_por_nombre' => $radicadoPorNombre,
                'fecha_inicio' => $this->formatearFechaCorreo((string) ($vacacion->fecha_inicio_vacacion ?? $vacacion->fecha_inicio ?? ''), 'Y-m-d'),
                'fecha_fin' => $this->formatearFechaCorreo((string) ($vacacion->fecha_fin_vacacion ?? $vacacion->fecha_fin ?? ''), 'Y-m-d'),
                'observacion' => trim((string) ($vacacion->observacion ?? '')),
                'aprobador_real_documento' => $documentoAprobador,
                'aprobador_real_nombre' => $nombreAprobador,
                'aprobador_real_correo' => $correoAprobador,
                'url_accion' => trim((string) $urlAccion) !== '' ? trim((string) $urlAccion) : $this->obtenerUrlGestionRrhh(),
                'texto_accion' => 'Ir a gestion RRHH',
                'fecha_radicado' => $this->formatearFechaCorreo((string) ($vacacion->fecha_creacion ?? ''), 'Y-m-d H:i:s'),
            ];

            $adjuntosCorreo = $this->vacacionDocumentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: $documentoPersona
            );

            Mail::to($destino)->queue(new NuevaVacacionRadicadaMail(
                datos: $datos,
                adjuntos: $adjuntosCorreo,
                asunto: 'Vacaciones pendientes de aprobacion RRHH'
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de vacaciones pendiente RRHH', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => $idNovedad,
                'destino' => $destino,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarPendienteRrhhPermisoPermanente(
        string $idNovedad,
        array $actor,
        ?string $urlAccion = null,
        string $canal = 'WEB',
        bool $encolar = true
    ): bool {
        if ($encolar) {
            return $this->encolarNotificacionGenerica(
                tipo: self::TIPO_PERMISO_PERMANENTE_PENDIENTE_RRHH,
                payload: [
                    'id_novedad' => $idNovedad,
                    'actor' => $actor,
                    'url_accion' => $urlAccion,
                ],
                canal: $canal,
                contexto: ['id_novedad' => $idNovedad]
            );
        }

        $destino = $this->correoNotificacionRrhh();
        if ($destino === '') {
            return false;
        }

        try {
            $detalle = $this->permisoPermanenteService->obtenerDetalleCorreo($idNovedad);
            $persona = is_array($detalle['persona'] ?? null) ? $detalle['persona'] : [];
            $permiso = is_object($detalle['permiso'] ?? null) ? $detalle['permiso'] : null;
            if (! $permiso) {
                return false;
            }

            $documentoPersona = trim((string) ($persona['identificacion'] ?? $permiso->documento_persona ?? ''));
            $documentoRadica = trim((string) ($permiso->documento_radica ?? ''));
            $radicadoPorNombre = '';
            if ($documentoRadica !== '') {
                $personaRadica = $this->permisoService->buscarPersona($documentoRadica);
                if (is_array($personaRadica)) {
                    $radicadoPorNombre = trim((string) ($personaRadica['nombre'] ?? ''));
                }
            }

            $documentoAprobador = trim((string) ($actor['documento'] ?? ''));
            if ($documentoAprobador === '') {
                $documentoAprobador = trim((string) ($permiso->documento_aprobador ?? ''));
            }
            $nombreAprobador = trim((string) ($actor['nombre'] ?? ''));
            if ($nombreAprobador === '') {
                $nombreAprobador = trim((string) ($permiso->nombre_aprobador ?? ''));
            }

            $correoAprobador = $documentoAprobador !== ''
                ? $this->actorNovedadService->obtenerCorreoUsuarioPorDocumento($documentoAprobador)
                : '';
            if ($correoAprobador === '') {
                $correoAprobador = trim((string) ($permiso->correo_aprobador ?? ''));
            }

            $datos = [
                'titulo_correo' => 'Permiso permanente pendiente de aprobacion RRHH',
                'mensaje_correo' => 'Una solicitud de permiso permanente ya fue aprobada por jefe y requiere gestion de RRHH.',
                'id_novedad' => $idNovedad,
                'empleado_documento' => $documentoPersona,
                'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
                'radicado_por_documento' => $documentoRadica,
                'radicado_por_nombre' => $radicadoPorNombre,
                'fecha_inicio' => $this->formatearFechaCorreo((string) ($permiso->fecha_inicio_permiso ?? $permiso->fecha_inicio ?? ''), 'Y-m-d'),
                'fecha_fin' => $this->formatearFechaCorreo((string) ($permiso->fecha_fin_permiso ?? $permiso->fecha_fin ?? ''), 'Y-m-d'),
                'jornada' => EmpleadoPermisoPermanenteService::opcionesJornada()[
                    EmpleadoPermisoPermanenteService::normalizarJornada((string) ($permiso->jornada ?? '')) ?? ''
                ] ?? trim((string) ($permiso->jornada ?? '')),
                'horario_fijo' => ((int) ($permiso->horario_fijo ?? 0)) === 1 ? 'SI' : 'NO',
                'observacion' => '',
                'aprobador_real_documento' => $documentoAprobador,
                'aprobador_real_nombre' => $nombreAprobador,
                'aprobador_real_correo' => $correoAprobador,
                'url_accion' => trim((string) $urlAccion) !== '' ? trim((string) $urlAccion) : $this->obtenerUrlGestionRrhh(),
                'texto_accion' => 'Ir a gestion RRHH',
                'fecha_radicado' => $this->formatearFechaCorreo((string) ($permiso->fecha_creacion ?? ''), 'Y-m-d H:i:s'),
            ];

            $adjuntosCorreo = $this->permisoPermanenteDocumentoService->construirAdjuntosCorreoDesdeNovedad(
                idNovedad: $idNovedad,
                identificacion: $documentoPersona
            );

            Mail::to($destino)->queue(new NuevoPermisoPermanenteRadicadoMail(
                datos: $datos,
                adjuntos: $adjuntosCorreo,
                asunto: 'Permiso permanente pendiente de aprobacion RRHH'
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Error enviando correo de permiso permanente pendiente RRHH', [
                'canal' => strtoupper(trim($canal)),
                'id_novedad' => $idNovedad,
                'destino' => $destino,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function construirDatosCorreoPendienteRrhh(array $detalle, array $actor, ?string $urlAccion): array
    {
        $permiso = is_object($detalle['permiso'] ?? null) ? $detalle['permiso'] : null;
        $novedad = is_object($detalle['novedad'] ?? null) ? $detalle['novedad'] : null;
        $persona = is_array($detalle['persona'] ?? null) ? $detalle['persona'] : [];

        $motivoLabel = trim((string) ($detalle['motivo_label'] ?? ''));
        if ($motivoLabel === '') {
            $motivoLabel = 'N/A';
        }

        $motivoCodigo = strtoupper(trim((string) ($permiso?->motivo_catalogo ?? '')));
        $otroMotivo = trim((string) ($permiso?->otro_motivo ?? ''));
        if ($motivoCodigo === 'OTROS' && $otroMotivo !== '') {
            $motivoLabel .= ' - '.$otroMotivo;
        }

        $fechaRadicado = $this->formatearFechaCorreo($novedad?->fecha_creacion, 'Y-m-d H:i:s');
        $urlAccion = trim((string) $urlAccion);

        return [
            'titulo_correo' => 'Permiso pendiente de aprobacion RRHH',
            'mensaje_correo' => 'Un jefe aprobo un permiso y ahora requiere gestion del area de RRHH.',
            'texto_accion' => 'Ir a pendientes RRHH',
            'url_accion' => $urlAccion !== '' ? $urlAccion : $this->obtenerUrlPendientesRrhh(),
            'nota_accion' => 'Abre la vista "Permisos pendientes de aprobacion RRHH" para aprobar o rechazar.',
            'url_login' => $this->obtenerUrlLoginSistema(),
            'texto_login' => 'Ir al login',
            'nota_login' => 'Si no tienes acceso directo, inicia sesion con tus credenciales del sistema Logtrans.',
            'actor_label' => 'Aprobado por jefe',
            'fecha_permiso' => $this->formatearFechaCorreo($novedad?->fecha_inicio, 'Y-m-d'),
            'hora_salida' => $this->formatearFechaCorreo($novedad?->fecha_inicio, 'h:i A'),
            'hora_ingreso' => $this->formatearFechaCorreo($novedad?->fecha_fin, 'h:i A'),
            'motivo' => $motivoLabel,
            'actividad' => trim((string) ($permiso?->actividad ?? '')),
            'empleado_documento' => trim((string) ($persona['identificacion'] ?? '')),
            'empleado_nombre' => trim((string) ($persona['nombre'] ?? '')),
            'radicado_por_documento' => trim((string) ($actor['documento'] ?? '')),
            'radicado_por_nombre' => trim((string) ($actor['nombre'] ?? '')),
            'jefe_nombre' => trim((string) ($actor['nombre'] ?? '')),
            'jefe_documento' => trim((string) ($actor['documento'] ?? '')),
            'jefe_correo' => $this->actorNovedadService->obtenerCorreoUsuarioPorDocumento((string) ($actor['documento'] ?? '')),
            'fecha_radicado' => $fechaRadicado !== '' ? $fechaRadicado : now()->format('Y-m-d H:i:s'),
        ];
    }

    private function construirAdjuntoPdfPermisoRadicado(string $idNovedad): ?array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        $detalle = $this->permisoService->obtenerDetalleParaPdf($idNovedad);
        $pdf = $this->permisoPdfService->generarDesdeDetalle($detalle);

        return [
            'contenido' => $pdf,
            'nombre' => 'permiso-empleado-radicado.pdf',
        ];
    }

    private function resolverMotivoLabel(array $payload): string
    {
        $motivoCodigo = strtoupper(trim((string) ($payload['motivo'] ?? '')));
        $motivoLabel = EmpleadoPermisoService::opcionesMotivo()[$motivoCodigo] ?? $motivoCodigo;

        if ($motivoCodigo === 'OTROS') {
            $detalleOtroMotivo = trim((string) ($payload['otro_motivo'] ?? ''));
            if ($detalleOtroMotivo !== '') {
                $motivoLabel .= ' - '.$detalleOtroMotivo;
            }
        }

        return $motivoLabel !== '' ? $motivoLabel : 'N/A';
    }

    private function extraerPayloadCorreoPermiso(array $payload): array
    {
        return [
            'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
            'fecha_permiso' => trim((string) ($payload['fecha_permiso'] ?? '')),
            'hora_salida' => trim((string) ($payload['hora_salida'] ?? '')),
            'hora_ingreso' => trim((string) ($payload['hora_ingreso'] ?? '')),
            'motivo' => trim((string) ($payload['motivo'] ?? '')),
            'otro_motivo' => trim((string) ($payload['otro_motivo'] ?? '')),
            'actividad' => trim((string) ($payload['actividad'] ?? '')),
        ];
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

    private function formatearHoraCorreoDesdeTexto(string $hora): string
    {
        $hora = trim($hora);
        if ($hora === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('H:i', $hora)->format('h:i A');
        } catch (\Throwable) {
            return $hora;
        }
    }

    private function obtenerUrlLoginSistema(): string
    {
        try {
            return route('login.form');
        } catch (\Throwable) {
            return URL::to('/login');
        }
    }

    private function obtenerUrlPendientesRrhh(): string
    {
        try {
            return route('gestionRRHH.permisos.rrhh');
        } catch (\Throwable) {
            return URL::to('/gestionRRHH/permisos/pendientes-rrhh');
        }
    }

    private function obtenerUrlGestionJefe(): string
    {
        try {
            return route('gestionRRHH.permisos.jefe');
        } catch (\Throwable) {
            return URL::to('/gestionRRHH/permisos/jefe');
        }
    }

    private function obtenerUrlGestionRrhh(): string
    {
        try {
            return route('gestionRRHH.permisos.rrhh');
        } catch (\Throwable) {
            return URL::to('/gestionRRHH/permisos/rrhh');
        }
    }

    private function correoNotificacionRadicado(): string
    {
        return trim((string) config('services.employee_permits.notifications.radicado_email', 'desarrollo3@copetran.com'));
    }

    private function correoNotificacionRrhh(): string
    {
        return trim((string) config('services.employee_permits.notifications.rrhh_email', 'desarrollo3@copetran.com'));
    }

    private function resolverDestinoNotificacionGestionInicial(?string $correoResponsable): string
    {
        $forzarCorreoPruebas = filter_var(
            config('services.employee_permits.notifications.force_notification_email', true),
            FILTER_VALIDATE_BOOL
        );

        $correoQuemado = $this->correoNotificacionRadicado();
        if ($forzarCorreoPruebas) {
            return $correoQuemado;
        }

        $correoResponsable = trim((string) $correoResponsable);
        if ($correoResponsable !== '') {
            return $correoResponsable;
        }

        return $correoQuemado;
    }

}
