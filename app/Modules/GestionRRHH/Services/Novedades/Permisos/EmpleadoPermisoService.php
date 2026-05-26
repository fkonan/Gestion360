<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Permisos;

use App\Models\User;
use App\Modules\GestionRRHH\Services\JefeEquipoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadPerfLogger;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoPermisoService
{
    private const MAX_OBSERVACION_NOVEDAD = 3900;
    private const MAX_OBSERVACION_HISTORIAL = 1900;

    public const ESTADO_RADICADO = 'RADICADO';
    public const ESTADO_JEFE_APROBADO = 'JEFE_APROBADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_ANULADO = 'ANULADO';

    private const ROL_RRHH = 'GESTOR RECURSO HUMANO';
    private const ROL_JEFE = 'JEFE';
    private const ROL_JEFE_ID = 10;
    private const MOTIVOS_AREA_ID = 'EMPLEADOS-MOTIVOS-PERMISO';
    private const CACHE_MOTIVOS_KEY = 'empleados_permisos_motivos_oracle_v1';
    private const CACHE_MOTIVOS_TTL_SECONDS = 900;

    private const MOTIVOS_FALLBACK = [
        'REUNION_ESCOLAR' => 'REUNION ESCOLAR',
        'CITA_MEDICA_FAMILIARES' => 'CITA MEDICA FAMILIARES',
        'ESTUDIO' => 'ESTUDIO',
        'ACTIVIDAD_LABORAL_EXTERNA' => 'ACTIVIDAD LABORAL EXTERNA',
        'MEDICINA_GENERAL' => 'MEDICINA GENERAL',
        'MEDICINA_ESPECIALIZADA' => 'MEDICINA ESPECIALIZADA',
        'TERAPIAS' => 'TERAPIAS',
        'ODONTOLOGIA' => 'ODONTOLOGIA',
        'URGENCIA_O_CITA_PRIORITARIA' => 'URGENCIAS O CITA PRIORITARIA',
        'ACCIDENTE_DE_TRABAJO' => 'ACCIDENTE DE TRABAJO',
        'EXAMENES' => 'EXAMENES',
        'OTROS' => 'OTROS',
    ];

    private const META_MOTIVO_CODIGO = 'MOTIVO_CODIGO';
    private const META_OTRO_MOTIVO = 'OTRO_MOTIVO';
    private const META_ORIGEN = 'ORIGEN';
    private const META_CREADO_POR_DOCUMENTO = 'CREADO_POR_DOCUMENTO';
    private const META_CREADO_POR_NOMBRE = 'CREADO_POR_NOMBRE';
    private const META_IP_EQUIPO = 'IP_EQUIPO';
    private const META_SISTEMA_ORIGEN = 'SISTEMA_ORIGEN';
    private const META_ANULACION_MOTIVO = 'ANULACION_MOTIVO';
    private const META_RECHAZO_NIVEL = 'RECHAZO_NIVEL';
    private const META_RECHAZO_MOTIVO = 'RECHAZO_MOTIVO';

    public function __construct(
        private readonly JefeEquipoService $jefeEquipoService
    ) {}

    public static function reglasCreacion(): array
    {
        $motivos = self::opcionesMotivo();

        return [
            'identificacion' => 'required|string|max:50',
            'fecha_permiso' => 'required|date_format:Y-m-d',
            'hora_salida' => 'required|date_format:H:i',
            'hora_ingreso' => 'nullable|date_format:H:i',
            'motivo' => 'required|string|in:'.implode(',', array_keys($motivos)),
            'otro_motivo' => 'nullable|string|max:255|required_if:motivo,OTROS',
            'actividad' => 'required|string|max:1000',
        ];
    }

    public static function mensajesCreacion(): array
    {
        return [
            'identificacion.required' => 'La identificacion del empleado es obligatoria.',
            'fecha_permiso.required' => 'La fecha del permiso es obligatoria.',
            'fecha_permiso.date_format' => 'La fecha del permiso debe tener formato YYYY-MM-DD.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.date_format' => 'La hora de salida debe tener formato HH:MM.',
            'hora_ingreso.date_format' => 'La hora de ingreso debe tener formato HH:MM.',
            'motivo.required' => 'Debe seleccionar un motivo.',
            'motivo.in' => 'El motivo seleccionado no es valido.',
            'otro_motivo.required_if' => 'Debe indicar el detalle cuando selecciona OTROS.',
            'actividad.required' => 'Debe especificar la actividad a realizar.',
            'actividad.max' => 'La actividad no puede exceder 1000 caracteres.',
        ];
    }

    public static function reglasRechazo(): array
    {
        return [
            'nivel' => 'required|string|in:jefe,rrhh',
            'motivo_rechazo' => 'required|string|max:500',
        ];
    }

    public static function reglasAnulacion(): array
    {
        return [
            'motivo_anulacion' => 'required|string|max:500',
        ];
    }

    public static function mensajesRechazo(): array
    {
        return [
            'nivel.required' => 'El nivel del rechazo es obligatorio.',
            'nivel.in' => 'El nivel de rechazo no es valido.',
            'motivo_rechazo.required' => 'Debe indicar el motivo del rechazo.',
            'motivo_rechazo.max' => 'El motivo de rechazo no puede exceder 500 caracteres.',
        ];
    }

    public static function mensajesAnulacion(): array
    {
        return [
            'motivo_anulacion.required' => 'Debe indicar la razon de la anulacion.',
            'motivo_anulacion.max' => 'La razon de anulacion no puede exceder 500 caracteres.',
        ];
    }

    public static function opcionesMotivo(): array
    {
        try {
            return Cache::remember(self::CACHE_MOTIVOS_KEY, self::CACHE_MOTIVOS_TTL_SECONDS, function () {
                $rows = DB::connection('oracle-360')
                    ->table('PAR_PARAMETROS')
                    ->where('area_id', self::MOTIVOS_AREA_ID)
                    ->where(function ($query) {
                        $query->whereNull('activo')
                            ->orWhere('activo', 1);
                    })
                    ->orderByRaw('NVL(parametro_orden, 999999)')
                    ->orderBy('parametro_valor')
                    ->get([
                        'parametro_valorcorto',
                        'parametro_valor2',
                        'parametro_valor',
                        'parametro_descripcion',
                    ]);

                $motivos = [];
                foreach ($rows as $row) {
                    $codigo = strtoupper(trim((string) ($row->parametro_valor2 ?? '')));
                    if ($codigo === '') {
                        $codigo = strtoupper(trim((string) ($row->parametro_valorcorto ?? '')));
                    }
                    $label = trim((string) ($row->parametro_valor ?? ''));

                    if ($codigo === '') {
                        $codigo = strtoupper(
                            trim(
                                preg_replace('/[^A-Z0-9]+/', '_', Str::ascii($label !== '' ? $label : (string) ($row->parametro_descripcion ?? '')))
                            )
                        );
                    }

                    if ($codigo === '' || $label === '') {
                        continue;
                    }

                    $motivos[$codigo] = $label;
                }

                return $motivos !== [] ? $motivos : self::MOTIVOS_FALLBACK;
            });
        } catch (\Throwable) {
            return self::MOTIVOS_FALLBACK;
        }
    }

    public static function opcionesEstadoFlujo(): array
    {
        return [
            self::ESTADO_RADICADO => 'Radicado',
            self::ESTADO_JEFE_APROBADO => 'Aprobado por jefe',
            self::ESTADO_APROBADO => 'Aprobado por RRHH',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_ANULADO => 'Anulado',
        ];
    }

    public function crearPermiso(
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        string $origen = 'WEB',
        ?string $ipEquipo = null,
        ?string $sistemaOrigen = null
    ): array {
        $perf = NovedadPerfLogger::start('radicar_permiso', [
            'origen' => strtoupper(trim($origen)),
            'documento_actor' => trim($documentoActor),
            'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
        ]);

        $persona = $this->buscarPersona($payload['identificacion']);
        if (! $persona) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'persona_no_encontrada',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el empleado activo en PER_PERSONAS.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'buscar_persona');

        $fechaBase = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_permiso']);
        $fechaInicio = $fechaBase->copy()->setTimeFromTimeString((string) $payload['hora_salida']);
        $horaIngreso = $this->normalizarTexto($payload['hora_ingreso'] ?? null);
        $fechaFin = null;
        if ($horaIngreso !== null) {
            $fechaFin = $fechaBase->copy()->setTimeFromTimeString($horaIngreso);
            if ($fechaFin->lt($fechaInicio)) {
                $fechaFin->addDay();
            }
        }
        NovedadPerfLogger::checkpoint($perf, 'normalizar_fechas');

        $tipoPermisoId = $this->obtenerTipoPermisoId();
        if (! $tipoPermisoId) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'tipo_novedad_no_encontrado',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el tipo de novedad Permiso en EMP_NOVEDADES_TIPO.',
                'http_status' => 500,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_tipo_novedad');

        $idNovedad = Str::uuid()->toString();
        $idPermiso = Str::uuid()->toString();
        $actividad = mb_substr($this->normalizarTexto($payload['actividad'] ?? '') ?? '', 0, self::MAX_OBSERVACION_NOVEDAD, 'UTF-8');
        $motivo = strtoupper(trim((string) ($payload['motivo'] ?? '')));
        $otroMotivo = $this->normalizarTexto($payload['otro_motivo'] ?? null);
        $jefeDirecto = $this->obtenerJefeDirectoDelEmpleado((string) $persona['identificacion']);

        try {
            DB::connection('oracle-360')->transaction(function () use (
                $idNovedad,
                $idPermiso,
                $persona,
                $tipoPermisoId,
                $fechaInicio,
                $fechaFin,
                $actividad,
                $motivo,
                $otroMotivo,
                $payload,
                $documentoActor,
                $nombreActor,
                $origen,
                $ipEquipo,
                $sistemaOrigen
            ) {
                DB::connection('oracle-360')
                    ->table('EMP_PERMISOS')
                    ->insert([
                        'id' => $idPermiso,
                        'documento_persona' => (string) $persona['identificacion'],
                        'documento_radica' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                        'motivo' => $motivo,
                        'otro_motivo' => $otroMotivo,
                        'actividad' => $actividad !== '' ? $actividad : null,
                        'observacion' => $actividad !== '' ? $actividad : null,
                        'estado' => self::ESTADO_RADICADO,
                        'origen' => strtoupper(trim($origen)),
                        'ip_equipo' => $this->normalizarTexto($ipEquipo),
                        'sistema_origen' => $this->normalizarTexto($sistemaOrigen),
                        'fecha_creacion' => now(),
                    ]);

                DB::connection('oracle-360')
                    ->table('EMP_NOVEDADES')
                    ->insert([
                        'id' => $idNovedad,
                        'id_persona' => (string) $persona['identificacion'],
                        'id_tipo_novedad' => $tipoPermisoId,
                        'id_origen' => $idPermiso,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'estado' => self::ESTADO_RADICADO,
                        'fecha_creacion' => now(),
                        'usuario_creacion' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                        'observacion' => $actividad !== '' ? $actividad : null,
                    ]);

            });
            NovedadPerfLogger::checkpoint($perf, 'transaccion_oracle360');

            $respuesta = [
                'ok' => true,
                'message' => 'Permiso radicado correctamente.',
                'id_novedad' => $idNovedad,
                'estado' => self::ESTADO_RADICADO,
                'jefe_directo' => $jefeDirecto,
            ];
            NovedadPerfLogger::finish($perf, [
                'ok' => true,
                'id_novedad' => $idNovedad,
            ]);

            return $respuesta;
        } catch (\Throwable $e) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'message' => 'No fue posible registrar el permiso.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }
    }

    public function obtenerPermisosPaginados(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->nuevaConsultaPermisos360()
            ->select([
                'n.id as emp_novedad_id',
                'n.id_persona',
                'n.estado as estado_flujo',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
                'n.fecha_creacion',
                'n.id_origen',
                'p.documento_persona',
                'p.documento_radica',
                'p.motivo',
                'p.otro_motivo',
                'p.actividad',
                'p.observacion as permiso_observacion',
            ]);

        $personaExacta = trim((string) ($filtros['persona_exacta'] ?? ''));
        if ($personaExacta !== '') {
            $query->where('n.id_persona', $personaExacta);
        } else {
            $persona = trim((string) ($filtros['persona'] ?? ''));
            if ($persona !== '') {
                $query->where('n.id_persona', 'like', '%'.$persona.'%');
            }
        }

        $estadoFlujo = trim((string) ($filtros['estado_flujo'] ?? ''));
        if ($estadoFlujo !== '') {
            $query->where('n.estado', $estadoFlujo);
        }

        $creadoPorDocumento = trim((string) ($filtros['creado_por_documento'] ?? ''));
        if ($creadoPorDocumento !== '') {
            $query->whereExists(function ($subquery) use ($creadoPorDocumento) {
                $subquery->select(DB::raw(1))
                    ->from('EMP_NOVEDADES_HISTORIAL as h')
                    ->whereColumn('h.id_novedad', 'n.id')
                    ->where('h.estado_nuevo', self::ESTADO_RADICADO)
                    ->where('h.usuario_accion', $creadoPorDocumento);
            });
        }

        $permisos = $query
            ->orderByDesc('n.fecha_creacion')
            ->paginate($perPage)
            ->withQueryString();

        return $this->enriquecerPermisosPaginados($permisos);
    }

    public function actualizarHorasNovedadPermiso(
        string $idNovedad,
        string $horaInicio,
        ?string $horaFin,
        ?string $documentoActor = null
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return [
                'ok' => false,
                'message' => 'No se encontro la novedad de permiso a actualizar.',
            ];
        }

        try {
            $fechaInicio = Carbon::parse($novedad->fecha_inicio);
            $fechaInicio->setTimeFromTimeString($horaInicio);

            $camposUpdate = [
                'fecha_inicio' => $fechaInicio,
                'fecha_modifica' => now(),
            ];

            $horaFin = $this->normalizarTexto($horaFin);
            if ($horaFin !== null) {
                $fechaFinBase = $novedad->fecha_fin ? Carbon::parse($novedad->fecha_fin) : $fechaInicio->copy();
                $fechaFinBase->setTimeFromTimeString($horaFin);
                if ($fechaFinBase->lt($fechaInicio)) {
                    $fechaFinBase->addDay();
                }
                $camposUpdate['fecha_fin'] = $fechaFinBase;
            }

            $documentoActor = $this->normalizarTexto($documentoActor);
            if ($documentoActor !== null) {
                $camposUpdate['usuario_modifica'] = $documentoActor;
            }

            $actualizados = DB::connection('oracle-360')
                ->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->whereIn('id_tipo_novedad', function ($query) {
                    $query->select('id')
                        ->from('EMP_NOVEDADES_TIPO')
                        ->where(function ($subquery) {
                            $subquery->whereRaw('UPPER(NVL(tabla, \'\')) = ?', ['EMP_PERMISOS'])
                                ->orWhereRaw('UPPER(NVL(descripcion, \'\')) = ?', ['PERMISO']);
                        });
                })
                ->update($camposUpdate);

            if ($actualizados < 1) {
                return [
                    'ok' => false,
                    'message' => 'No fue posible actualizar la novedad de permiso.',
                ];
            }

            return [
                'ok' => true,
                'message' => 'Horas de la novedad actualizadas correctamente.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible actualizar las horas de la novedad.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function aprobarPorJefe(
        string $idNovedad,
        string $documentoActor,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        if ((string) $novedad->estado !== self::ESTADO_RADICADO) {
            return ['ok' => false, 'message' => 'El permiso no esta pendiente de aprobacion de jefe.'];
        }

        if (! $esSuperAdmin && ! $this->esJefeDirectoDeEmpleado($documentoActor, (string) $novedad->id_persona)) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de jefe para aprobar este permiso.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_JEFE_APROBADO,
                documentoActor: $documentoActor,
                estadoEsperado: self::ESTADO_RADICADO
            );

            return ['ok' => true, 'message' => 'Permiso aprobado por jefe correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible aprobar el permiso por jefe.');
        }
    }

    public function aprobarPorRrhh(
        string $idNovedad,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        if ((string) $novedad->estado !== self::ESTADO_JEFE_APROBADO) {
            return ['ok' => false, 'message' => 'El permiso no esta pendiente de aprobacion de RRHH.'];
        }

        if (! $esSuperAdmin && ! $esRrhh) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para aprobar este permiso.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_APROBADO,
                documentoActor: $documentoActor,
                estadoEsperado: self::ESTADO_JEFE_APROBADO
            );

            return ['ok' => true, 'message' => 'Permiso aprobado por RRHH correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible aprobar el permiso por RRHH.');
        }
    }

    public function rechazarPermiso(
        string $idNovedad,
        string $nivel,
        string $motivoRechazo,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        $nivel = strtolower(trim($nivel));
        if ($nivel === 'jefe') {
            if ((string) $novedad->estado !== self::ESTADO_RADICADO) {
                return ['ok' => false, 'message' => 'El permiso no esta pendiente de rechazo por jefe.'];
            }

            if (! $esSuperAdmin && ! $this->esJefeDirectoDeEmpleado($documentoActor, (string) $novedad->id_persona)) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de jefe para rechazar este permiso.'];
            }
        } elseif ($nivel === 'rrhh') {
            if ((string) $novedad->estado !== self::ESTADO_JEFE_APROBADO) {
                return ['ok' => false, 'message' => 'El permiso no esta pendiente de rechazo por RRHH.'];
            }

            if (! $esSuperAdmin && ! $esRrhh) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para rechazar este permiso.'];
            }
        } else {
            return ['ok' => false, 'message' => 'El nivel de rechazo no es valido.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_RECHAZADO,
                documentoActor: $documentoActor,
                observacion: $this->construirObservacionRechazo(
                    $novedad->observacion ?? null,
                    $motivoRechazo,
                    $nivel
                ),
                estadoEsperado: (string) $novedad->estado
            );

            return ['ok' => true, 'message' => 'Permiso rechazado correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible rechazar el permiso.');
        }
    }

    public function anularPermiso(
        string $idNovedad,
        string $motivoAnulacion,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $motivoAnulacion = (string) ($this->normalizarTexto($motivoAnulacion) ?? '');
        if ($motivoAnulacion === '') {
            return ['ok' => false, 'message' => 'Debe indicar la razon de la anulacion.'];
        }

        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        $esTitular = trim($documentoActor) !== '' && trim($documentoActor) === trim((string) $novedad->id_persona);
        $esCreador = $this->fueCreadoPorUsuario((string) $novedad->id, $documentoActor);
        if (! $esSuperAdmin && ! $esRrhh && ! $esTitular && ! $esCreador) {
            return ['ok' => false, 'message' => 'No tienes autorizacion para anular este permiso.'];
        }

        if (in_array((string) $novedad->estado, [self::ESTADO_APROBADO, self::ESTADO_RECHAZADO, self::ESTADO_ANULADO], true)) {
            return ['ok' => false, 'message' => 'El permiso no puede anularse desde el estado actual.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_ANULADO,
                documentoActor: $documentoActor,
                observacion: $this->construirObservacionAnulacion($novedad->observacion ?? null, $motivoAnulacion),
                estadoEsperado: (string) $novedad->estado
            );

            return ['ok' => true, 'message' => 'Permiso anulado correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible anular el permiso.');
        }
    }

    public function esUsuarioRrhh(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(self::ROL_RRHH) || $user->hasRole(User::SUPER_ADMIN_ROLE);
    }

    public function esUsuarioSuperAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(User::SUPER_ADMIN_ROLE);
    }

    public function esUsuarioJefe(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(self::ROL_JEFE)
            || $user->roles()->where('roles.id', self::ROL_JEFE_ID)->exists();
    }

    public function obtenerPermisosPaginadosPorJefe(
        string $documentoJefe,
        array $filtros = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $documentoJefe = trim($documentoJefe);
        if ($documentoJefe === '') {
            return $this->nuevaConsultaPermisos360()
                ->whereRaw('1 = 0')
                ->select([
                    'n.id as emp_novedad_id',
                    'n.id_persona',
                    'n.estado as estado_flujo',
                    'n.fecha_inicio',
                    'n.fecha_fin',
                    'n.observacion',
                    'n.fecha_creacion',
                    'n.id_origen',
                    'p.documento_persona',
                    'p.documento_radica',
                    'p.motivo',
                    'p.otro_motivo',
                    'p.actividad',
                    'p.observacion as permiso_observacion',
                ])
                ->paginate($perPage)
                ->withQueryString();
        }

        $empleados = $this->jefeEquipoService->obtenerEmpleadosDirectos($documentoJefe);
        $documentosEquipo = $empleados
            ->pluck('doc_empleado')
            ->map(fn ($doc) => trim((string) $doc))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query = $this->nuevaConsultaPermisos360()
            ->select([
                'n.id as emp_novedad_id',
                'n.id_persona',
                'n.estado as estado_flujo',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
                'n.fecha_creacion',
                'n.id_origen',
                'p.documento_persona',
                'p.documento_radica',
                'p.motivo',
                'p.otro_motivo',
                'p.actividad',
                'p.observacion as permiso_observacion',
            ]);

        if ($documentosEquipo === []) {
            $query->whereRaw('1 = 0');
        } else {
            $this->aplicarWhereInSegmentado($query, 'n.id_persona', $documentosEquipo);
        }

        $estadoFlujo = trim((string) ($filtros['estado_flujo'] ?? ''));
        if ($estadoFlujo !== '') {
            $query->where('n.estado', $estadoFlujo);
        }

        $permisos = $query
            ->orderByDesc('n.fecha_creacion')
            ->paginate($perPage)
            ->withQueryString();

        return $this->enriquecerPermisosPaginados($permisos);
    }

    public function puedeAprobarJefe(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_RADICADO) {
            return false;
        }

        if ($this->esUsuarioSuperAdmin($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        return $this->esJefeDirectoDeEmpleado($documentoActor, $documentoEmpleado);
    }

    public function puedeAprobarRrhh(?User $user, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_JEFE_APROBADO) {
            return false;
        }

        return $this->esUsuarioRrhh($user);
    }

    public function puedeRechazarJefe(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_RADICADO) {
            return false;
        }

        if ($this->esUsuarioSuperAdmin($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        return $this->esJefeDirectoDeEmpleado($documentoActor, $documentoEmpleado);
    }

    public function usuarioPuedeConsultarPermiso(?User $user, string $idNovedad): bool
    {
        if (! $user) {
            return false;
        }

        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return false;
        }

        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return false;
        }

        if ($this->esUsuarioSuperAdmin($user) || $this->esUsuarioRrhh($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        $documentoEmpleado = trim((string) ($novedad->id_persona ?? ''));
        if ($documentoEmpleado !== '' && $documentoActor === $documentoEmpleado) {
            return true;
        }

        if ($this->fueCreadoPorUsuario((string) $novedad->id, $documentoActor)) {
            return true;
        }

        return $documentoEmpleado !== ''
            && $this->esJefeDirectoDeEmpleado($documentoActor, $documentoEmpleado);
    }

    public function obtenerSeguimientoPermiso(string $idNovedad): ?array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return null;
        }

        $persona = $this->buscarPersona((string) $novedad->id_persona) ?? [];
        $estadoActual = strtoupper(trim((string) ($novedad->estado ?? '')));
        $eventos = $this->obtenerEventosSeguimientoPermiso($idNovedad);
        $radicador = $this->obtenerRadicadoresPorNovedades([$idNovedad])[$idNovedad] ?? [];
        $metadatosNovedad = $this->parsearObservacionMetadatos((string) ($novedad->observacion ?? ''));

        $eventoRadicado = $eventos[self::ESTADO_RADICADO] ?? null;
        $pasos = [
            $this->construirPasoSeguimiento(
                clave: self::ESTADO_RADICADO,
                titulo: 'Radicacion',
                descripcion: 'La solicitud fue registrada y enviada para aprobacion del jefe.',
                estadoVisual: 'completado',
                fecha: $eventoRadicado['fecha'] ?? $this->formatearFechaSeguimiento($novedad->fecha_creacion ?? null),
                actorDocumento: $radicador['documento'] ?? ($eventoRadicado['actor_documento'] ?? null),
                actorNombre: $radicador['nombre'] ?? ($eventoRadicado['actor_nombre'] ?? null)
            ),
        ];

        $jefeCompletado = isset($eventos[self::ESTADO_JEFE_APROBADO])
            || in_array($estadoActual, [self::ESTADO_JEFE_APROBADO, self::ESTADO_APROBADO], true);
        $rrhhCompletado = isset($eventos[self::ESTADO_APROBADO]) || $estadoActual === self::ESTADO_APROBADO;
        $flujoDetenido = in_array($estadoActual, [self::ESTADO_RECHAZADO, self::ESTADO_ANULADO], true);

        $eventoJefe = $eventos[self::ESTADO_JEFE_APROBADO] ?? null;
        $eventoRechazo = $eventos[self::ESTADO_RECHAZADO] ?? null;
        $motivoRechazo = $this->normalizarTexto($metadatosNovedad[self::META_RECHAZO_MOTIVO] ?? null)
            ?? $this->normalizarTexto($eventoRechazo['metadatos'][self::META_RECHAZO_MOTIVO] ?? null);
        $nivelRechazo = strtoupper((string) (
            $this->normalizarTexto($metadatosNovedad[self::META_RECHAZO_NIVEL] ?? null)
            ?? $this->normalizarTexto($eventoRechazo['metadatos'][self::META_RECHAZO_NIVEL] ?? null)
            ?? ''
        ));
        if ($estadoActual === self::ESTADO_RECHAZADO && $nivelRechazo === '') {
            $nivelRechazo = $jefeCompletado || (($eventoRechazo['estado_anterior'] ?? '') === self::ESTADO_JEFE_APROBADO)
                ? 'RRHH'
                : 'JEFE';
        }
        $rechazadoPorJefe = $estadoActual === self::ESTADO_RECHAZADO && $nivelRechazo !== 'RRHH';
        $rechazadoPorRrhh = $estadoActual === self::ESTADO_RECHAZADO && $nivelRechazo === 'RRHH';

        $pasos[] = $this->construirPasoSeguimiento(
            clave: self::ESTADO_JEFE_APROBADO,
            titulo: $rechazadoPorJefe ? 'Solicitud rechazada por jefe' : 'Aprobacion del jefe',
            descripcion: $rechazadoPorJefe
                ? 'El jefe directo rechazo la solicitud y el flujo fue detenido.'
                : 'El jefe directo revisa y aprueba la salida.',
            estadoVisual: $rechazadoPorJefe
                ? 'detenido'
                : ($jefeCompletado ? 'completado' : ($estadoActual === self::ESTADO_RADICADO ? 'actual' : 'pendiente')),
            fecha: $rechazadoPorJefe ? ($eventoRechazo['fecha'] ?? null) : ($eventoJefe['fecha'] ?? null),
            actorDocumento: $rechazadoPorJefe ? ($eventoRechazo['actor_documento'] ?? null) : ($eventoJefe['actor_documento'] ?? null),
            actorNombre: $rechazadoPorJefe ? ($eventoRechazo['actor_nombre'] ?? null) : ($eventoJefe['actor_nombre'] ?? null),
            detalle: $rechazadoPorJefe ? $motivoRechazo : null
        );

        $eventoRrhh = $eventos[self::ESTADO_APROBADO] ?? null;
        $pasos[] = $this->construirPasoSeguimiento(
            clave: self::ESTADO_APROBADO,
            titulo: $rechazadoPorRrhh ? 'Solicitud rechazada por RRHH' : 'Aprobacion de RRHH',
            descripcion: $rechazadoPorRrhh
                ? 'RRHH rechazo la solicitud y el flujo fue detenido.'
                : 'RRHH realiza la aprobacion final para que el permiso sea valido.',
            estadoVisual: $rechazadoPorRrhh
                ? 'detenido'
                : ($rrhhCompletado ? 'completado' : ($estadoActual === self::ESTADO_JEFE_APROBADO ? 'actual' : 'pendiente')),
            fecha: $rechazadoPorRrhh ? ($eventoRechazo['fecha'] ?? null) : ($eventoRrhh['fecha'] ?? null),
            actorDocumento: $rechazadoPorRrhh ? ($eventoRechazo['actor_documento'] ?? null) : ($eventoRrhh['actor_documento'] ?? null),
            actorNombre: $rechazadoPorRrhh ? ($eventoRechazo['actor_nombre'] ?? null) : ($eventoRrhh['actor_nombre'] ?? null),
            detalle: $rechazadoPorRrhh ? $motivoRechazo : null
        );

        if ($estadoActual === self::ESTADO_ANULADO) {
            $eventoAnulacion = $eventos[self::ESTADO_ANULADO] ?? null;
            $motivo = $this->normalizarTexto($metadatosNovedad[self::META_ANULACION_MOTIVO] ?? null)
                ?? $this->normalizarTexto($eventoAnulacion['metadatos'][self::META_ANULACION_MOTIVO] ?? null);

            $pasos[] = $this->construirPasoSeguimiento(
                clave: self::ESTADO_ANULADO,
                titulo: 'Solicitud anulada',
                descripcion: 'La solicitud fue anulada y ya no continua el flujo.',
                estadoVisual: 'detenido',
                fecha: $eventoAnulacion['fecha'] ?? null,
                actorDocumento: $eventoAnulacion['actor_documento'] ?? null,
                actorNombre: $eventoAnulacion['actor_nombre'] ?? null,
                detalle: $motivo
            );
        }

        return [
            'id_novedad' => $idNovedad,
            'estado_actual' => $estadoActual,
            'estado_actual_label' => self::opcionesEstadoFlujo()[$estadoActual] ?? $estadoActual,
            'resumen' => $this->resumenSeguimientoPermiso($estadoActual),
            'empleado' => [
                'documento' => trim((string) ($novedad->id_persona ?? '')),
                'nombre' => trim((string) ($persona['nombre'] ?? '')),
            ],
            'pasos' => $pasos,
            'flujo_detenido' => $flujoDetenido,
        ];
    }

    public function puedeRechazarRrhh(?User $user, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_JEFE_APROBADO) {
            return false;
        }

        return $this->esUsuarioRrhh($user);
    }

    public function puedeAnular(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user || ! in_array($estadoFlujo, [self::ESTADO_RADICADO, self::ESTADO_JEFE_APROBADO], true)) {
            return false;
        }

        if ($this->esUsuarioRrhh($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);

        return $documentoActor !== '' && $documentoActor === trim($documentoEmpleado);
    }

    public function obtenerDocumentoUsuario(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $documento = $user->persona?->PerNumDoc ?? $user->IdUsuario ?? '';

        return trim((string) $documento);
    }

    public function obtenerNombreUsuario(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $nombre = $user->persona?->nombreCompleto()
            ?? trim((string) ($user->Usuario ?? ''));

        return trim((string) $nombre);
    }

    public function obtenerDetalleParaPdf(string $idNovedad): array
    {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            throw new RuntimeException('No se encontro el registro en EMP_NOVEDADES.');
        }

        $persona = $this->buscarPersona((string) $novedad->id_persona);
        if (! $persona) {
            throw new RuntimeException('No se encontro la persona en PER_PERSONAS.');
        }

        $firma = $this->obtenerFirmasPorNovedades([(string) $novedad->id])[(string) $novedad->id] ?? [];
        $motivoData = $this->obtenerMotivosPorNovedades([(string) $novedad->id])[(string) $novedad->id] ?? [];
        $radicador = $this->obtenerRadicadoresPorNovedades([(string) $novedad->id])[(string) $novedad->id] ?? [];
        $motivoCodigo = strtoupper(trim((string) ($novedad->motivo ?? ($motivoData['motivo_catalogo'] ?? ''))));
        $catalogoMotivos = self::opcionesMotivo();

        $permiso = (object) [
            'emp_novedad_id' => (string) $novedad->id,
            'id_persona' => (string) $novedad->id_persona,
            'estado_flujo' => (string) $novedad->estado,
            'motivo_catalogo' => $motivoCodigo !== '' ? $motivoCodigo : null,
            'otro_motivo' => $this->normalizarTexto($novedad->otro_motivo ?? null) ?? ($motivoData['otro_motivo'] ?? null),
            'actividad' => $this->normalizarTexto($novedad->actividad ?? null)
                ?? $this->limpiarObservacionActividad($novedad->observacion ?? null),
            'jefe_aprobado_por_documento' => $firma['jefe_aprobado_por_documento'] ?? null,
            'jefe_aprobado_por_nombre' => $firma['jefe_aprobado_por_nombre'] ?? null,
            'rrhh_aprobado_por_documento' => $firma['rrhh_aprobado_por_documento'] ?? null,
            'rrhh_aprobado_por_nombre' => $firma['rrhh_aprobado_por_nombre'] ?? null,
            'firma_empleado_ip' => $this->normalizarTexto($motivoData['ip_equipo'] ?? null),
            'firma_empleado_sistema' => $this->normalizarTexto($motivoData['sistema_origen'] ?? null),
            'radicado_por_documento' => $this->normalizarTexto($novedad->documento_radica ?? null)
                ?? ($radicador['documento'] ?? ($motivoData['creado_por_documento'] ?? null)),
            'radicado_por_nombre' => $radicador['nombre'] ?? ($motivoData['creado_por_nombre'] ?? null),
        ];

        return [
            'permiso' => $permiso,
            'novedad' => $novedad,
            'persona' => $persona,
            'motivo_label' => $motivoCodigo !== '' && isset($catalogoMotivos[$motivoCodigo])
                ? $catalogoMotivos[$motivoCodigo]
                : 'N/A',
        ];
    }

    public function buscarPersona(string $identificacion): ?array
    {
        $identificacion = trim($identificacion);
        if ($identificacion === '') {
            return null;
        }

        return Cache::remember('per_persona_permiso_'.$identificacion, 300, function () use ($identificacion) {
            $row = DB::connection('oracle')
                ->table('per_personas as p')
                ->leftJoin('per_empresapersonas as ep', function ($join) {
                    $join->on('ep.pe_id_pe', '=', 'p.id')
                        ->whereIn('ep.tp_id', [1, 11])
                        ->where('ep.activo', 1)
                        ->where('ep.estborrado', 0)
                        ->whereNull('ep.fecfin');
                })
                ->leftJoin('per_cargoccostos as cc', function ($join) {
                    $join->on('cc.id', '=', 'ep.cc_id')
                        ->where('cc.activo', 1)
                        ->where('cc.estborrado', 0);
                })
                ->leftJoin('per_centrocostos as ct', function ($join) {
                    $join->on('ct.codigo', '=', 'cc.ct_codigo')
                        ->where('ct.estado', 1)
                        ->where('ct.estborrado', 0);
                })
                ->where('p.identificacion', $identificacion)
                ->where('p.estado', 'ACTIVO')
                ->where('p.estborrado', 0)
                ->orderByDesc('ep.id')
                ->selectRaw(
                    "p.identificacion as identificacion,
                    TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
                    TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) as nombre,
                    NVL(p.codigo, ep.codigo) as codigo,
                    p.sexo as sexo,
                    NVL(p.edad, FLOOR(MONTHS_BETWEEN(SYSDATE, p.fecnacimiento) / 12)) as edad,
                    ct.descripcion as seccion"
                )
                ->first();

            if (! $row) {
                return null;
            }

            return [
                'identificacion' => trim((string) ($row->identificacion ?? '')),
                'nombre' => trim((string) ($row->nombre ?? '')),
                'codigo' => trim((string) ($row->codigo ?? '')),
                'sexo' => trim((string) ($row->sexo ?? '')),
                'edad' => isset($row->edad) ? (string) $row->edad : '',
                'seccion' => trim((string) ($row->seccion ?? '')),
            ];
        });
    }

    public function obtenerRadicadoresPorNovedades(array $idsNovedad): array
    {
        $ids = $this->normalizarIds($idsNovedad);

        if ($ids === []) {
            return [];
        }

        $radicadores = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_PERMISOS as p', 'p.id', '=', 'n.id_origen')
            ->whereIn('n.id', $ids)
            ->select(['n.id as id_novedad', 'p.documento_radica'])
            ->get()
            ->mapWithKeys(function ($item) {
                $idNovedad = trim((string) ($item->id_novedad ?? ''));
                $documento = trim((string) ($item->documento_radica ?? ''));
                if ($idNovedad === '') {
                    return [];
                }

                return [$idNovedad => [
                    'documento' => $documento !== '' ? $documento : null,
                    'nombre' => null,
                ]];
            })
            ->all();

        $documentos = collect($radicadores)
            ->pluck('documento')
            ->filter()
            ->map(fn ($doc) => (string) $doc)
            ->unique()
            ->values()
            ->all();

        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->whereIn('id_novedad', $ids)
            ->where('estado_nuevo', self::ESTADO_RADICADO)
            ->select([
                'id_novedad',
                'usuario_accion',
                'observacion',
                'fecha_cambio',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        $documentosIndexados = array_fill_keys($documentos, true);

        foreach ($historial as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            if (! isset($radicadores[$idNovedad])) {
                $radicadores[$idNovedad] = [
                    'documento' => null,
                    'nombre' => null,
                ];
            }

            $metadatos = $this->parsearObservacionMetadatos((string) ($item->observacion ?? ''));
            $documentoMetadato = trim((string) ($metadatos[self::META_CREADO_POR_DOCUMENTO] ?? ''));
            $documento = $documentoMetadato !== ''
                ? $documentoMetadato
                : trim((string) ($item->usuario_accion ?? ''));

            $nombre = $this->normalizarTexto($metadatos[self::META_CREADO_POR_NOMBRE] ?? null);

            if ($documento !== '' && (empty($radicadores[$idNovedad]['documento']) || $documentoMetadato !== '')) {
                $radicadores[$idNovedad]['documento'] = $documento;
                $documentosIndexados[$documento] = true;
            }

            if ($nombre !== null && empty($radicadores[$idNovedad]['nombre'])) {
                $radicadores[$idNovedad]['nombre'] = $nombre;
            }
        }

        $nombresPorDocumento = $this->obtenerNombresPorDocumentos(array_keys($documentosIndexados));
        foreach ($radicadores as &$radicador) {
            $documento = $radicador['documento'];
            if ($documento !== null && empty($radicador['nombre'])) {
                $radicador['nombre'] = $nombresPorDocumento[$documento] ?? null;
            }
        }
        unset($radicador);

        return $radicadores;
    }

    private function enriquecerPermisosPaginados(LengthAwarePaginator $permisos): LengthAwarePaginator
    {
        $collection = $permisos->getCollection();
        if ($collection->isEmpty()) {
            return $permisos;
        }

        $docs = $collection->pluck('id_persona')->filter()->unique()->values()->all();
        $personas = [];
        foreach ($docs as $doc) {
            $personaInfo = $this->buscarPersona((string) $doc);
            if ($personaInfo) {
                $personas[(string) $doc] = $personaInfo;
            }
        }

        $idsNovedad = $collection->pluck('emp_novedad_id')->filter()->unique()->values()->all();
        $firmasPorNovedad = $this->obtenerFirmasPorNovedades($idsNovedad);
        $motivosPorNovedad = $this->obtenerMotivosPorNovedades($idsNovedad);
        $radicadoresPorNovedad = $this->obtenerRadicadoresPorNovedades($idsNovedad);
        $catalogoMotivos = self::opcionesMotivo();

        $collection->transform(function ($permiso) use ($personas, $firmasPorNovedad, $motivosPorNovedad, $radicadoresPorNovedad, $catalogoMotivos) {
            $persona = $personas[$permiso->id_persona] ?? null;
            $firma = $firmasPorNovedad[(string) ($permiso->emp_novedad_id ?? '')] ?? [];
            $motivoData = $motivosPorNovedad[(string) ($permiso->emp_novedad_id ?? '')] ?? [];
            $radicador = $radicadoresPorNovedad[(string) ($permiso->emp_novedad_id ?? '')] ?? [];

            $permiso->persona_nombre = $persona['nombre'] ?? 'Sin nombre';
            $permiso->persona_codigo = $persona['codigo'] ?? null;
            $permiso->persona_seccion = $persona['seccion'] ?? null;
            $permiso->persona_sexo = $persona['sexo'] ?? null;
            $permiso->persona_edad = $persona['edad'] ?? null;
            $permiso->novedad_estado = (string) ($permiso->estado_flujo ?? '');
            $permiso->fecha_inicio = isset($permiso->fecha_inicio) ? Carbon::parse($permiso->fecha_inicio) : null;
            $permiso->fecha_fin = isset($permiso->fecha_fin) ? Carbon::parse($permiso->fecha_fin) : null;
            $motivoCodigo = strtoupper(trim((string) ($permiso->motivo ?? ($motivoData['motivo_catalogo'] ?? ''))));
            $permiso->motivo_catalogo = $motivoCodigo !== '' ? $motivoCodigo : null;
            $permiso->motivo_label = $motivoCodigo !== '' && isset($catalogoMotivos[$motivoCodigo])
                ? $catalogoMotivos[$motivoCodigo]
                : 'N/A';
            $permiso->otro_motivo = $this->normalizarTexto($permiso->otro_motivo ?? null) ?? ($motivoData['otro_motivo'] ?? null);
            $permiso->actividad = $this->normalizarTexto($permiso->actividad ?? null)
                ?? $this->limpiarObservacionActividad($permiso->observacion ?? null);
            $permiso->jefe_aprobado_por_documento = $firma['jefe_aprobado_por_documento'] ?? null;
            $permiso->jefe_aprobado_por_nombre = $firma['jefe_aprobado_por_nombre'] ?? null;
            $permiso->rrhh_aprobado_por_documento = $firma['rrhh_aprobado_por_documento'] ?? null;
            $permiso->rrhh_aprobado_por_nombre = $firma['rrhh_aprobado_por_nombre'] ?? null;
            $permiso->radicado_por_documento = $this->normalizarTexto($permiso->documento_radica ?? null)
                ?? ($radicador['documento'] ?? ($motivoData['creado_por_documento'] ?? null));
            $permiso->radicado_por_nombre = $radicador['nombre'] ?? ($motivoData['creado_por_nombre'] ?? null);

            return $permiso;
        });

        $permisos->setCollection($collection);

        return $permisos;
    }

    private function aplicarWhereInSegmentado(Builder $query, string $columna, array $valores): void
    {
        $valores = collect($valores)
            ->map(fn ($valor) => trim((string) $valor))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($valores === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $chunks = array_chunk($valores, 900);

        $query->where(function ($subquery) use ($chunks, $columna) {
            foreach ($chunks as $index => $chunk) {
                if ($index === 0) {
                    $subquery->whereIn($columna, $chunk);
                    continue;
                }

                $subquery->orWhereIn($columna, $chunk);
            }
        });
    }

    private function obtenerEventosSeguimientoPermiso(string $idNovedad): array
    {
        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->where('id_novedad', $idNovedad)
            ->select([
                'estado_anterior',
                'estado_nuevo',
                'fecha_cambio',
                'usuario_accion',
                'observacion',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        $eventos = [];
        $documentos = [];

        foreach ($historial as $item) {
            $estadoNuevo = strtoupper(trim((string) ($item->estado_nuevo ?? '')));
            if ($estadoNuevo === '') {
                continue;
            }

            $metadatos = $this->parsearObservacionMetadatos((string) ($item->observacion ?? ''));
            $documentoActor = $this->normalizarTexto($item->usuario_accion ?? null)
                ?? $this->normalizarTexto($metadatos[self::META_CREADO_POR_DOCUMENTO] ?? null);
            $nombreActor = $this->normalizarTexto($metadatos[self::META_CREADO_POR_NOMBRE] ?? null);

            if ($documentoActor !== null) {
                $documentos[$documentoActor] = true;
            }

            if ($estadoNuevo === self::ESTADO_RADICADO && isset($eventos[$estadoNuevo])) {
                continue;
            }

            $eventos[$estadoNuevo] = [
                'estado_anterior' => strtoupper(trim((string) ($item->estado_anterior ?? ''))),
                'estado_nuevo' => $estadoNuevo,
                'fecha' => $this->formatearFechaSeguimiento($item->fecha_cambio ?? null),
                'actor_documento' => $documentoActor,
                'actor_nombre' => $nombreActor,
                'observacion' => $this->normalizarTexto($item->observacion ?? null),
                'metadatos' => $metadatos,
            ];
        }

        $nombresPorDocumento = $this->obtenerNombresPorDocumentos(array_keys($documentos));
        foreach ($eventos as &$evento) {
            $documentoActor = $evento['actor_documento'];
            if ($documentoActor !== null && empty($evento['actor_nombre'])) {
                $evento['actor_nombre'] = $nombresPorDocumento[$documentoActor] ?? null;
            }
        }
        unset($evento);

        return $eventos;
    }

    private function construirPasoSeguimiento(
        string $clave,
        string $titulo,
        string $descripcion,
        string $estadoVisual,
        ?string $fecha,
        ?string $actorDocumento,
        ?string $actorNombre,
        ?string $detalle = null
    ): array {
        $actorDocumento = $this->normalizarTexto($actorDocumento);
        $actorNombre = $this->normalizarTexto($actorNombre);

        return [
            'clave' => $clave,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'estado_visual' => $estadoVisual,
            'estado_visual_label' => match ($estadoVisual) {
                'completado' => 'Completado',
                'actual' => 'En proceso',
                'detenido' => 'Detenido',
                default => 'Pendiente',
            },
            'fecha' => $fecha,
            'actor_documento' => $actorDocumento,
            'actor_nombre' => $actorNombre,
            'detalle' => $this->normalizarTexto($detalle),
        ];
    }

    private function resumenSeguimientoPermiso(string $estadoActual): string
    {
        return match ($estadoActual) {
            self::ESTADO_RADICADO => 'Pendiente de aprobacion por el jefe directo.',
            self::ESTADO_JEFE_APROBADO => 'Pendiente de aprobacion final por RRHH.',
            self::ESTADO_APROBADO => 'El permiso ya cuenta con aprobacion final.',
            self::ESTADO_RECHAZADO => 'El permiso fue rechazado y no continua el flujo.',
            self::ESTADO_ANULADO => 'El permiso fue anulado y no continua el flujo.',
            default => 'Estado de seguimiento no reconocido.',
        };
    }

    private function formatearFechaSeguimiento(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y h:i A');
        } catch (\Throwable) {
            $texto = is_scalar($valor) ? trim((string) $valor) : '';

            return $texto !== '' ? $texto : null;
        }
    }

    private function obtenerTipoPermisoId(): ?string
    {
        return Cache::remember('emp_novedades_tipo_permiso_id', 300, function () {
            return DB::connection('oracle-360')
                ->table('EMP_NOVEDADES_TIPO')
                ->where(function ($query) {
                    $query->whereRaw('UPPER(NVL(tabla, \'\')) = ?', ['EMP_PERMISOS'])
                        ->orWhereRaw('UPPER(NVL(descripcion, \'\')) = ?', ['PERMISO']);
                })
                ->value('id');
        });
    }

    private function esJefeDirectoDeEmpleado(string $documentoJefe, string $documentoEmpleado): bool
    {
        return $this->jefeEquipoService->esJefeDirectoDeEmpleado($documentoJefe, $documentoEmpleado);
    }

    private function obtenerJefeDirectoDelEmpleado(string $documentoEmpleado): ?array
    {
        try {
            return $this->jefeEquipoService->obtenerJefeDirectoDeEmpleado($documentoEmpleado);
        } catch (\Throwable) {
            return null;
        }
    }

    private function obtenerNovedadPermiso(string $idNovedad): ?object
    {
        return $this->nuevaConsultaPermisos360()
            ->where('n.id', $idNovedad)
            ->select([
                'n.id',
                'n.id_persona',
                'n.id_origen',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
                'n.fecha_creacion',
                'p.documento_persona',
                'p.documento_radica',
                'p.motivo',
                'p.otro_motivo',
                'p.actividad',
                'p.observacion as permiso_observacion',
            ])
            ->first();
    }

    private function nuevaConsultaPermisos360(): Builder
    {
        return DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->leftJoin('EMP_PERMISOS as p', 'p.id', '=', 'n.id_origen')
            ->where(function ($query) {
                $query->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', ['EMP_PERMISOS'])
                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['PERMISO']);
            });
    }

    private function fueCreadoPorUsuario(string $idNovedad, string $documentoUsuario): bool
    {
        $idNovedad = trim($idNovedad);
        $documentoUsuario = trim($documentoUsuario);

        if ($idNovedad === '' || $documentoUsuario === '') {
            return false;
        }

        $creadoEnHistorial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->where('id_novedad', $idNovedad)
            ->where('estado_nuevo', self::ESTADO_RADICADO)
            ->where('usuario_accion', $documentoUsuario)
            ->exists();

        if ($creadoEnHistorial) {
            return true;
        }

        $documentoRadica = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_PERMISOS as p', 'p.id', '=', 'n.id_origen')
            ->where('n.id', $idNovedad)
            ->value('p.documento_radica');

        return trim((string) $documentoRadica) === $documentoUsuario;
    }

    private function obtenerMotivosPorNovedades(array $idsNovedad): array
    {
        $ids = $this->normalizarIds($idsNovedad);

        if ($ids === []) {
            return [];
        }

        $resultado = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_PERMISOS as p', 'p.id', '=', 'n.id_origen')
            ->whereIn('n.id', $ids)
            ->select([
                'n.id as id_novedad',
                'p.motivo',
                'p.otro_motivo',
                'p.ip_equipo',
                'p.sistema_origen',
                'p.documento_radica',
            ])
            ->get()
            ->mapWithKeys(function ($item) {
                $idNovedad = trim((string) ($item->id_novedad ?? ''));
                if ($idNovedad === '') {
                    return [];
                }

                return [$idNovedad => [
                    'motivo_catalogo' => strtoupper(trim((string) ($item->motivo ?? ''))),
                    'otro_motivo' => $this->normalizarTexto($item->otro_motivo ?? null),
                    'ip_equipo' => $this->normalizarTexto($item->ip_equipo ?? null),
                    'sistema_origen' => $this->normalizarTexto($item->sistema_origen ?? null),
                    'creado_por_documento' => $this->normalizarTexto($item->documento_radica ?? null),
                    'creado_por_nombre' => null,
                ]];
            })
            ->all();

        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->whereIn('id_novedad', $ids)
            ->where('estado_nuevo', self::ESTADO_RADICADO)
            ->whereRaw("UPPER(NVL(observacion, '')) LIKE ?", ['%'.self::META_MOTIVO_CODIGO.'=%'])
            ->select([
                'id_novedad',
                'usuario_accion',
                'observacion',
                'fecha_cambio',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        foreach ($historial as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            $metadatos = $this->parsearObservacionMetadatos((string) ($item->observacion ?? ''));
            $motivo = strtoupper(trim((string) ($metadatos[self::META_MOTIVO_CODIGO] ?? '')));
            if ($motivo === '') {
                continue;
            }

            if (! isset($resultado[$idNovedad])) {
                $resultado[$idNovedad] = [];
            }

            $resultado[$idNovedad] = array_merge([
                'motivo_catalogo' => $motivo,
                'otro_motivo' => $this->normalizarTexto($metadatos[self::META_OTRO_MOTIVO] ?? null),
                'ip_equipo' => $this->normalizarTexto($metadatos[self::META_IP_EQUIPO] ?? null),
                'sistema_origen' => $this->normalizarTexto($metadatos[self::META_SISTEMA_ORIGEN] ?? null),
                'creado_por_documento' => $this->normalizarTexto($metadatos[self::META_CREADO_POR_DOCUMENTO] ?? null)
                    ?? $this->normalizarTexto($item->usuario_accion ?? null),
                'creado_por_nombre' => $this->normalizarTexto($metadatos[self::META_CREADO_POR_NOMBRE] ?? null),
            ], $resultado[$idNovedad]);
        }

        return $resultado;
    }

    private function sanitizarValorMetadato(?string $valor): ?string
    {
        $normalizado = $this->normalizarTexto($valor);
        if ($normalizado === null || $normalizado === '') {
            return null;
        }

        return str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $normalizado);
    }

    private function construirObservacionAnulacion(?string $observacionActual, string $motivoAnulacion): string
    {
        $observacionBase = (string) ($this->normalizarTexto($observacionActual) ?? '');
        $motivoSanitizado = $this->sanitizarValorMetadato($motivoAnulacion) ?? 'NO_INDICADO';
        $segmentoAnulacion = self::META_ANULACION_MOTIVO.'='.$motivoSanitizado;

        $observacion = $observacionBase === ''
            ? $segmentoAnulacion
            : $observacionBase.' | '.$segmentoAnulacion;

        return mb_substr($observacion, 0, self::MAX_OBSERVACION_NOVEDAD, 'UTF-8');
    }

    private function construirObservacionRechazo(?string $observacionActual, string $motivoRechazo, string $nivel): string
    {
        $observacionBase = (string) ($this->normalizarTexto($observacionActual) ?? '');
        $motivoSanitizado = $this->sanitizarValorMetadato($motivoRechazo) ?? 'NO_INDICADO';
        $nivelSanitizado = strtoupper(trim((string) ($this->sanitizarValorMetadato($nivel) ?? 'NO_INDICADO')));
        $segmentoRechazo = self::META_RECHAZO_NIVEL.'='.$nivelSanitizado
            .' | '.self::META_RECHAZO_MOTIVO.'='.$motivoSanitizado;

        $observacion = $observacionBase === ''
            ? $segmentoRechazo
            : $observacionBase.' | '.$segmentoRechazo;

        return mb_substr($observacion, 0, self::MAX_OBSERVACION_NOVEDAD, 'UTF-8');
    }

    private function limpiarObservacionActividad(?string $observacion): ?string
    {
        $texto = (string) ($this->normalizarTexto($observacion) ?? '');
        if ($texto === '') {
            return null;
        }

        $patronMetadatos = '/\s\|\s(?:'
            .preg_quote(self::META_ANULACION_MOTIVO, '/')
            .'|'.preg_quote(self::META_RECHAZO_NIVEL, '/')
            .'|'.preg_quote(self::META_RECHAZO_MOTIVO, '/')
            .')=/i';
        $partes = preg_split($patronMetadatos, $texto, 2);
        $actividad = trim((string) ($partes[0] ?? ''));

        return $actividad === '' ? null : $actividad;
    }

    private function parsearObservacionMetadatos(string $observacion): array
    {
        $resultado = [];

        foreach (explode('|', $observacion) as $segmento) {
            $segmento = trim($segmento);
            if ($segmento === '' || ! str_contains($segmento, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $segmento, 2);
            $clave = strtoupper(trim((string) $clave));
            if ($clave === '') {
                continue;
            }

            $resultado[$clave] = trim((string) $valor);
        }

        return $resultado;
    }

    private function obtenerFirmasPorNovedades(array $idsNovedad): array
    {
        $ids = $this->normalizarIds($idsNovedad);

        if ($ids === []) {
            return [];
        }

        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->whereIn('id_novedad', $ids)
            ->whereIn('estado_nuevo', [self::ESTADO_JEFE_APROBADO, self::ESTADO_APROBADO])
            ->select([
                'id_novedad',
                'estado_nuevo',
                'usuario_accion',
                'fecha_cambio',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        $firmas = [];
        $documentos = [];

        foreach ($historial as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            if (! isset($firmas[$idNovedad])) {
                $firmas[$idNovedad] = [
                    'jefe_aprobado_por_documento' => null,
                    'jefe_aprobado_por_nombre' => null,
                    'rrhh_aprobado_por_documento' => null,
                    'rrhh_aprobado_por_nombre' => null,
                ];
            }

            $documentoAccion = trim((string) ($item->usuario_accion ?? ''));
            if ($documentoAccion !== '') {
                $documentos[$documentoAccion] = true;
            }

            $estadoNuevo = trim((string) ($item->estado_nuevo ?? ''));
            if ($estadoNuevo === self::ESTADO_JEFE_APROBADO && $documentoAccion !== '') {
                $firmas[$idNovedad]['jefe_aprobado_por_documento'] = $documentoAccion;
            }

            if ($estadoNuevo === self::ESTADO_APROBADO && $documentoAccion !== '') {
                $firmas[$idNovedad]['rrhh_aprobado_por_documento'] = $documentoAccion;
            }
        }

        $nombresPorDocumento = $this->obtenerNombresPorDocumentos(array_keys($documentos));
        foreach ($firmas as &$firma) {
            $docJefe = $firma['jefe_aprobado_por_documento'];
            $docRrhh = $firma['rrhh_aprobado_por_documento'];
            $firma['jefe_aprobado_por_nombre'] = $docJefe ? ($nombresPorDocumento[$docJefe] ?? null) : null;
            $firma['rrhh_aprobado_por_nombre'] = $docRrhh ? ($nombresPorDocumento[$docRrhh] ?? null) : null;
        }
        unset($firma);

        return $firmas;
    }

    private function obtenerNombresPorDocumentos(array $documentos): array
    {
        $documentos = $this->normalizarIds($documentos);

        if ($documentos === []) {
            return [];
        }

        $nombres = $this->obtenerNombresUsuariosActivosPorDocumentos($documentos);
        $documentosPendientes = array_values(array_diff($documentos, array_keys($nombres)));

        if ($documentosPendientes !== []) {
            $nombresPerPersonas = $this->obtenerNombresPerPersonasActivasPorDocumentos($documentosPendientes);
            foreach ($nombresPerPersonas as $documento => $nombre) {
                if (! isset($nombres[$documento])) {
                    $nombres[$documento] = $nombre;
                }
            }
        }

        return $nombres;
    }

    private function obtenerNombresUsuariosActivosPorDocumentos(array $documentos): array
    {
        $nombres = [];

        foreach (array_chunk($documentos, 900) as $chunk) {
            try {
                $filas = DB::connection('mysql-gestion-admin')
                    ->table('_usuarios as u')
                    ->join('_personas as p', 'p.IdPersona', '=', 'u.idPersona')
                    ->where('u.UsuarioEstado', 'ACTIVO')
                    ->where('p.PerEstado', 'ACTIVO')
                    ->whereIn('p.PerNumDoc', $chunk)
                    ->selectRaw("p.PerNumDoc as documento, UPPER(TRIM(CONCAT(p.PerNombres, ' ', p.PerApellidos))) as nombre")
                    ->orderByDesc('u.IdUsuario')
                    ->get();
            } catch (\Throwable) {
                continue;
            }

            foreach ($filas as $fila) {
                $documento = trim((string) ($fila->documento ?? ''));
                if ($documento === '' || isset($nombres[$documento])) {
                    continue;
                }

                $nombre = $this->normalizarTexto($fila->nombre ?? null);
                if ($nombre !== null) {
                    $nombres[$documento] = $nombre;
                }
            }
        }

        return $nombres;
    }

    private function obtenerNombresPerPersonasActivasPorDocumentos(array $documentos): array
    {
        $nombres = [];

        foreach (array_chunk($documentos, 900) as $chunk) {
            $filas = DB::connection('oracle')
                ->table('per_personas as p')
                ->whereIn('p.identificacion', $chunk)
                ->where('p.estado', 'ACTIVO')
                ->where('p.estborrado', 0)
                ->selectRaw(
                    "p.identificacion as identificacion,
                    TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
                    TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) as nombre"
                )
                ->get();

            foreach ($filas as $fila) {
                $documento = trim((string) ($fila->identificacion ?? ''));
                if ($documento === '') {
                    continue;
                }

                $nombre = $this->normalizarTexto($fila->nombre ?? null);
                if ($nombre !== null) {
                    $nombres[$documento] = $nombre;
                }
            }
        }

        return $nombres;
    }

    private function actualizarEstadoNovedad(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        ?string $observacion = null,
        ?string $estadoEsperado = null
    ): void {
        DB::connection('oracle-360')->transaction(function () use (
            $idNovedad,
            $nuevoEstado,
            $documentoActor,
            $observacion,
            $estadoEsperado
        ) {
            $novedadActual = DB::connection('oracle-360')
                ->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->select(['id', 'id_origen', 'estado'])
                ->lockForUpdate()
                ->first();

            if (! $novedadActual) {
                throw new RuntimeException('NOVEDAD_NO_ENCONTRADA');
            }

            $estadoAnterior = trim((string) ($novedadActual->estado ?? ''));
            $estadoEsperado = $this->normalizarTexto($estadoEsperado);
            if ($estadoEsperado !== null && $estadoAnterior !== $estadoEsperado) {
                throw new RuntimeException('ESTADO_CAMBIO_CONCURRENTE');
            }

            $camposNovedad = [
                'estado' => $nuevoEstado,
                'fecha_modifica' => now(),
                'usuario_modifica' => $documentoActor,
            ];

            if ($observacion !== null) {
                $camposNovedad['observacion'] = $observacion;
            }

            DB::connection('oracle-360')
                ->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->update($camposNovedad);

            $idOrigen = trim((string) ($novedadActual->id_origen ?? ''));
            if ($idOrigen !== '') {
                $camposPermiso = [
                    'estado' => $nuevoEstado,
                    'fecha_modifica' => now(),
                ];

                if ($observacion !== null) {
                    $camposPermiso['observacion'] = $observacion;
                }

                DB::connection('oracle-360')
                    ->table('EMP_PERMISOS')
                    ->where('id', $idOrigen)
                    ->update($camposPermiso);
            }

        });
    }

    private function construirErrorCambioEstado(\Throwable $e, string $mensajeBase): array
    {
        $mensajeError = (string) $e->getMessage();
        $mensajeErrorUpper = strtoupper($mensajeError);
        if (str_contains($mensajeErrorUpper, 'ESTADO_CAMBIO_CONCURRENTE')) {
            return [
                'ok' => false,
                'message' => 'El estado del permiso cambio antes de guardar la accion. Actualiza la pagina e intenta nuevamente.',
                'error' => $mensajeError,
            ];
        }

        if (
            str_contains($mensajeErrorUpper, 'CK_EMP_NOVEDADES_ESTADO')
            && str_contains($mensajeErrorUpper, 'JEFE_APROBADO')
        ) {
            return [
                'ok' => false,
                'message' => 'La base de datos aun no permite el estado JEFE_APROBADO en EMP_NOVEDADES. Debes ajustar la restriccion CK_EMP_NOVEDADES_ESTADO.',
                'error' => $mensajeError,
            ];
        }

        if (
            str_contains($mensajeErrorUpper, 'CK_EMP_NOV_HIST_ESTADO_NUEVO')
            || str_contains($mensajeErrorUpper, 'CK_EMP_NOV_HIST_ESTADO_ANT')
        ) {
            return [
                'ok' => false,
                'message' => 'La base de datos no permite JEFE_APROBADO en EMP_NOVEDADES_HISTORIAL. Debes ajustar las restricciones CK_EMP_NOV_HIST_ESTADO_NUEVO y CK_EMP_NOV_HIST_ESTADO_ANT.',
                'error' => $mensajeError,
            ];
        }

        return [
            'ok' => false,
            'message' => $mensajeBase,
            'error' => $mensajeError,
        ];
    }

    private function normalizarTexto(mixed $value): ?string
    {
        $texto = trim((string) $value);

        return $texto === '' ? null : $texto;
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }
}

