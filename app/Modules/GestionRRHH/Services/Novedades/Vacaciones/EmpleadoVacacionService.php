<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Vacaciones;

use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\GestionRRHH\Services\JefeEquipoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadPerfLogger;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoVacacionService
{
    private const CONNECTION = 'oracle-360';
    private const MAX_OBSERVACION = 1000;

    public const ESTADO_RADICADO = 'RADICADO';
    public const ESTADO_JEFE_APROBADO = 'JEFE_APROBADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_ANULADO = 'ANULADO';

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly JefeEquipoService $jefeEquipoService,
        private readonly EmpleadoVacacionDocumentoService $documentoService
    ) {}

    public static function reglasCreacion(): array
    {
        return [
            'identificacion' => 'required|string|max:50',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'observacion' => 'nullable|string|max:1000',
        ];
    }

    public static function mensajesCreacion(): array
    {
        return [
            'identificacion.required' => 'La identificacion del empleado es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener formato YYYY-MM-DD.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'observacion.max' => 'La observacion no puede exceder 1000 caracteres.',
        ];
    }

    public function crearVacacion(
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        string $origen = 'WEB',
        ?string $ipEquipo = null,
        ?string $sistemaOrigen = null
    ): array {
        $perf = NovedadPerfLogger::start('radicar_vacacion', [
            'origen' => strtoupper(trim($origen)),
            'documento_actor' => trim($documentoActor),
            'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
        ]);

        $persona = $this->permisoService->buscarPersona((string) ($payload['identificacion'] ?? ''));
        if (! $persona) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'persona_no_encontrada',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el empleado activo para radicar vacaciones.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'buscar_persona');

        $tipoNovedadId = $this->documentoService->obtenerTipoNovedadVacacionId();
        if ($tipoNovedadId === null) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'tipo_novedad_no_encontrado',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el tipo de novedad VACACION en EMP_NOVEDADES_TIPO.',
                'http_status' => 500,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_tipo_novedad');

        $documentoPersona = trim((string) ($persona['identificacion'] ?? ''));
        $aprobador = $this->resolverAprobadorInicial($documentoPersona);
        if (! ($aprobador['ok'] ?? false)) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'aprobador_no_resuelto',
            ]);
            return [
                'ok' => false,
                'message' => (string) ($aprobador['message'] ?? 'No fue posible resolver el aprobador inicial de la vacacion.'),
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_aprobador');

        $requiereAprobacionRrhh = $this->requiereAprobacionRrhh($documentoPersona);
        $fechaInicio = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_fin'])->endOfDay();
        $observacion = $this->limpiarObservacion((string) ($payload['observacion'] ?? ''));
        $idNovedad = (string) Str::uuid();
        $idDetalle = (string) Str::uuid();
        $documentoActor = trim($documentoActor);

        try {
            DB::connection(self::CONNECTION)->transaction(function () use (
                $idNovedad,
                $idDetalle,
                $documentoPersona,
                $documentoActor,
                $observacion,
                $payload,
                $tipoNovedadId,
                $fechaInicio,
                $fechaFin,
                $origen,
                $ipEquipo,
                $sistemaOrigen,
                $aprobador,
                $requiereAprobacionRrhh
            ) {
                DB::connection(self::CONNECTION)
                    ->table('EMP_VACACIONES')
                    ->insert([
                        'id' => $idDetalle,
                        'documento_persona' => $documentoPersona,
                        'documento_radica' => $documentoActor !== '' ? $documentoActor : $documentoPersona,
                        'fecha_inicio' => $payload['fecha_inicio'],
                        'fecha_fin' => $payload['fecha_fin'],
                        'observacion' => $observacion,
                        'estado' => self::ESTADO_RADICADO,
                        'origen' => strtoupper(trim($origen)),
                        'ip_equipo' => $this->normalizarTexto($ipEquipo),
                        'sistema_origen' => $this->normalizarTexto($sistemaOrigen),
                        'tipo_aprobador' => (string) ($aprobador['tipo'] ?? null),
                        'documento_aprobador' => (string) ($aprobador['documento'] ?? null),
                        'nombre_aprobador' => (string) ($aprobador['nombre'] ?? null),
                        'correo_aprobador' => (string) ($aprobador['correo'] ?? null),
                        'requiere_aprobacion_rrhh' => $requiereAprobacionRrhh ? 1 : 0,
                        'fecha_creacion' => now(),
                    ]);

                DB::connection(self::CONNECTION)
                    ->table('EMP_NOVEDADES')
                    ->insert([
                        'id' => $idNovedad,
                        'id_persona' => $documentoPersona,
                        'id_tipo_novedad' => $tipoNovedadId,
                        'id_origen' => $idDetalle,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'estado' => self::ESTADO_RADICADO,
                        'fecha_creacion' => now(),
                        'usuario_creacion' => $documentoActor !== '' ? $documentoActor : $documentoPersona,
                        'observacion' => $observacion,
                    ]);

            });
            NovedadPerfLogger::checkpoint($perf, 'transaccion_oracle360');
        } catch (\Throwable $e) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'error' => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'message' => 'No fue posible radicar la solicitud de vacaciones.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }

        $respuesta = [
            'ok' => true,
            'message' => 'Vacaciones radicadas correctamente.',
            'id_novedad' => $idNovedad,
            'id_detalle' => $idDetalle,
            'estado' => self::ESTADO_RADICADO,
            'aprobador' => [
                'tipo' => (string) ($aprobador['tipo'] ?? ''),
                'documento' => (string) ($aprobador['documento'] ?? ''),
                'nombre' => (string) ($aprobador['nombre'] ?? ''),
                'correo' => (string) ($aprobador['correo'] ?? ''),
            ],
            'requiere_aprobacion_rrhh' => $requiereAprobacionRrhh,
        ];
        NovedadPerfLogger::finish($perf, [
            'ok' => true,
            'id_novedad' => $idNovedad,
        ]);

        return $respuesta;
    }

    public function aprobarPorJefe(string $idNovedad, string $documentoActor, bool $esSuperAdmin = false): array
    {
        $vacacion = $this->obtenerNovedadVacacion($idNovedad);
        if (! $vacacion) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de vacaciones.'];
        }

        if ((string) $vacacion->estado !== self::ESTADO_RADICADO) {
            return ['ok' => false, 'message' => 'La solicitud no esta pendiente de aprobacion inicial.'];
        }

        if (! $esSuperAdmin && ! $this->actorPuedeAprobarInicial($documentoActor, $vacacion)) {
            return ['ok' => false, 'message' => 'No tienes autorizacion para aprobar esta solicitud de vacaciones.'];
        }

        try {
            if ((int) ($vacacion->requiere_aprobacion_rrhh ?? 1) === 1) {
                $this->cambiarEstadoVacacion(
                    idNovedad: $idNovedad,
                    nuevoEstado: self::ESTADO_JEFE_APROBADO,
                    documentoActor: $documentoActor,
                    tipoEvento: 'APROBACION_JEFE',
                    estadoEsperado: self::ESTADO_RADICADO
                );

                return [
                    'ok' => true,
                    'message' => 'Vacaciones aprobadas por jefe. Pendiente gestion de RRHH.',
                    'estado' => self::ESTADO_JEFE_APROBADO,
                    'requiere_aprobacion_rrhh' => true,
                ];
            }

            $resultadoBloqueo = $this->generarBloqueoSiAplica($vacacion);
            if ($resultadoBloqueo === 'error') {
                return [
                    'ok' => false,
                    'message' => 'No fue posible generar el bloqueo asociado a la novedad de vacaciones.',
                ];
            }

            DB::connection(self::CONNECTION)->transaction(function () use ($idNovedad, $documentoActor) {
                $this->cambiarEstadoVacacionInterno(
                    idNovedad: $idNovedad,
                    nuevoEstado: self::ESTADO_JEFE_APROBADO,
                    documentoActor: $documentoActor,
                    tipoEvento: 'APROBACION_JEFE',
                    estadoEsperado: self::ESTADO_RADICADO
                );

                $this->cambiarEstadoVacacionInterno(
                    idNovedad: $idNovedad,
                    nuevoEstado: self::ESTADO_APROBADO,
                    documentoActor: $documentoActor,
                    tipoEvento: 'APROBACION_JEFE_FINAL',
                    estadoEsperado: self::ESTADO_JEFE_APROBADO
                );
            });

            return [
                'ok' => true,
                'message' => $resultadoBloqueo === 'bloqueado'
                    ? 'Vacaciones aprobadas. Se genero el bloqueo asociado.'
                    : 'Vacaciones aprobadas. Este caso no requiere gestion de RRHH.',
                'estado' => self::ESTADO_APROBADO,
                'requiere_aprobacion_rrhh' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible aprobar la solicitud de vacaciones.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function aprobarPorRrhh(string $idNovedad, string $documentoActor, bool $esRrhh, bool $esSuperAdmin = false): array
    {
        $vacacion = $this->obtenerNovedadVacacion($idNovedad);
        if (! $vacacion) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de vacaciones.'];
        }

        if ((int) ($vacacion->requiere_aprobacion_rrhh ?? 1) !== 1) {
            return ['ok' => false, 'message' => 'Esta solicitud de vacaciones no requiere aprobacion de RRHH.'];
        }

        if ((string) $vacacion->estado !== self::ESTADO_JEFE_APROBADO) {
            return ['ok' => false, 'message' => 'La solicitud no esta pendiente de aprobacion de RRHH.'];
        }

        if (! $esSuperAdmin && ! $esRrhh) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para aprobar esta solicitud.'];
        }

        $resultadoBloqueo = $this->generarBloqueoSiAplica($vacacion);
        if ($resultadoBloqueo === 'error') {
            return [
                'ok' => false,
                'message' => 'No fue posible generar el bloqueo asociado a la novedad de vacaciones.',
            ];
        }

        return $this->cambiarEstadoVacacion(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_APROBADO,
            documentoActor: $documentoActor,
            tipoEvento: 'APROBACION_RRHH',
            estadoEsperado: self::ESTADO_JEFE_APROBADO,
            mensajeOk: $resultadoBloqueo === 'bloqueado'
                ? 'Vacaciones aprobadas por RRHH y bloqueo asociado generado correctamente.'
                : 'Vacaciones aprobadas por RRHH correctamente.'
        );
    }

    public function rechazarVacacion(
        string $idNovedad,
        string $nivel,
        string $motivoRechazo,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $vacacion = $this->obtenerNovedadVacacion($idNovedad);
        if (! $vacacion) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de vacaciones.'];
        }

        $nivel = strtolower(trim($nivel));
        $motivoRechazo = trim($motivoRechazo);
        if ($motivoRechazo === '') {
            return ['ok' => false, 'message' => 'Debes indicar el motivo del rechazo.'];
        }

        if ($nivel === 'jefe') {
            if ((string) $vacacion->estado !== self::ESTADO_RADICADO) {
                return ['ok' => false, 'message' => 'La solicitud no esta pendiente de rechazo en nivel inicial.'];
            }

            if (! $esSuperAdmin && ! $this->actorPuedeAprobarInicial($documentoActor, $vacacion)) {
                return ['ok' => false, 'message' => 'No tienes autorizacion para rechazar esta solicitud.'];
            }
        } elseif ($nivel === 'rrhh') {
            if ((string) $vacacion->estado !== self::ESTADO_JEFE_APROBADO) {
                return ['ok' => false, 'message' => 'La solicitud no esta pendiente de rechazo por RRHH.'];
            }

            if (! $esSuperAdmin && ! $esRrhh) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para rechazar esta solicitud.'];
            }
        } else {
            return ['ok' => false, 'message' => 'El nivel del rechazo no es valido.'];
        }

        return $this->cambiarEstadoVacacion(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_RECHAZADO,
            documentoActor: $documentoActor,
            tipoEvento: 'RECHAZO',
            estadoEsperado: (string) $vacacion->estado,
            observacion: $motivoRechazo,
            mensajeOk: 'Vacaciones rechazadas correctamente.'
        );
    }

    public function anularVacacion(
        string $idNovedad,
        string $motivoAnulacion,
        string $documentoActor,
        bool $esSuperAdmin = false
    ): array {
        $vacacion = $this->obtenerNovedadVacacion($idNovedad);
        if (! $vacacion) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de vacaciones.'];
        }

        $motivoAnulacion = trim($motivoAnulacion);
        if ($motivoAnulacion === '') {
            return ['ok' => false, 'message' => 'Debes indicar la razon de la anulacion.'];
        }

        $documentoActor = trim($documentoActor);
        $documentoPersona = trim((string) ($vacacion->documento_persona ?? $vacacion->id_persona ?? ''));
        if (! $esSuperAdmin && $documentoActor !== $documentoPersona) {
            return ['ok' => false, 'message' => 'Solo la persona titular de la solicitud puede anular vacaciones.'];
        }

        if (in_array((string) $vacacion->estado, [self::ESTADO_APROBADO, self::ESTADO_RECHAZADO, self::ESTADO_ANULADO], true)) {
            return ['ok' => false, 'message' => 'La solicitud no puede anularse desde el estado actual.'];
        }

        return $this->cambiarEstadoVacacion(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_ANULADO,
            documentoActor: $documentoActor,
            tipoEvento: 'ANULACION',
            estadoEsperado: (string) $vacacion->estado,
            observacion: $motivoAnulacion,
            mensajeOk: 'Vacaciones anuladas correctamente.'
        );
    }

    public function obtenerNovedadVacacion(string $idNovedad): ?object
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_VACACIONES as v', 'v.id', '=', 'n.id_origen')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->where('n.id', $idNovedad)
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(t.tabla, '')) = ?", ['EMP_VACACIONES'])
                    ->orWhereRaw("UPPER(NVL(t.descripcion, '')) = ?", ['VACACION']);
            })
            ->select([
                'n.id',
                'n.id_persona',
                'n.id_origen',
                'n.estado',
                'n.fecha_creacion',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
                'v.documento_persona',
                'v.documento_radica',
                'v.estado as estado_detalle',
                'v.fecha_inicio as fecha_inicio_vacacion',
                'v.fecha_fin as fecha_fin_vacacion',
                'v.tipo_aprobador',
                'v.documento_aprobador',
                'v.nombre_aprobador',
                'v.correo_aprobador',
                'v.requiere_aprobacion_rrhh',
                DB::raw('NVL(t.requiere_bloqueo, 0) as requiere_bloqueo_tipo'),
                DB::raw('NVL(t.id_bloqueo_logtrans, 0) as id_bloqueo_logtrans_tipo'),
                DB::raw('NVL(t.id_bloqueo_fics, 0) as id_bloqueo_fics_tipo'),
            ])
            ->first();
    }

    private function generarBloqueoSiAplica(object $vacacion): string|bool
    {
        if ((int) ($vacacion->requiere_bloqueo_tipo ?? 0) !== 1) {
            return false;
        }

        $documentoPersona = trim((string) ($vacacion->documento_persona ?? $vacacion->id_persona ?? ''));
        if ($documentoPersona === '') {
            return false;
        }

        $idBloqueoLogtrans = (int) ($vacacion->id_bloqueo_logtrans_tipo ?? 0);
        if ($idBloqueoLogtrans < 1) {
            $idBloqueoLogtrans = BloqueoService::ID_BLOQUEO_LOGTRANS_VACACION;
        }

        $idBloqueoFics = (int) ($vacacion->id_bloqueo_fics_tipo ?? 0);
        if ($idBloqueoFics < 1) {
            $idBloqueoFics = BloqueoService::ID_BLOQUEO_FICS_VACACION;
        }

        $descripcion = 'VAC '.$this->normalizarTexto($vacacion->fecha_inicio_vacacion ?? $vacacion->fecha_inicio ?? '')
            .' AL '.$this->normalizarTexto($vacacion->fecha_fin_vacacion ?? $vacacion->fecha_fin ?? '')
            .' APROBADO POR RRHH POR gestion.copetran.com.co';

        $resultadoLogtrans = BloqueoService::crearNovedadEmpleado(
            $documentoPersona,
            $descripcion,
            $idBloqueoLogtrans,
            $vacacion->fecha_inicio_vacacion ?? $vacacion->fecha_inicio ?? null,
            $vacacion->fecha_fin_vacacion ?? $vacacion->fecha_fin ?? null
        );

        $resultadoFics = BloqueoService::crearBloqueoFICS(
            $documentoPersona,
            $idBloqueoFics,
            $vacacion->fecha_inicio_vacacion ?? $vacacion->fecha_inicio ?? null,
            $vacacion->fecha_fin_vacacion ?? $vacacion->fecha_fin ?? null
        );

        return $resultadoLogtrans || $resultadoFics
            ? 'bloqueado'
            : false;
    }

    public function esNovedadVacacion(string $idNovedad): bool
    {
        return $this->obtenerNovedadVacacion($idNovedad) !== null;
    }

    public function obtenerDetalleCorreo(string $idNovedad): ?array
    {
        $vacacion = $this->obtenerNovedadVacacion($idNovedad);
        if (! $vacacion) {
            return null;
        }

        $persona = $this->permisoService->buscarPersona((string) ($vacacion->documento_persona ?? $vacacion->id_persona ?? ''));

        return [
            'vacacion' => $vacacion,
            'persona' => $persona,
        ];
    }

    private function cambiarEstadoVacacion(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        string $tipoEvento,
        string $estadoEsperado,
        ?string $observacion = null,
        ?string $mensajeOk = null
    ): array {
        try {
            $this->cambiarEstadoVacacionInterno(
                idNovedad: $idNovedad,
                nuevoEstado: $nuevoEstado,
                documentoActor: $documentoActor,
                tipoEvento: $tipoEvento,
                estadoEsperado: $estadoEsperado,
                observacion: $observacion
            );

            return [
                'ok' => true,
                'message' => $mensajeOk ?? 'Estado de vacaciones actualizado correctamente.',
                'estado' => $nuevoEstado,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible actualizar el estado de la solicitud de vacaciones.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function cambiarEstadoVacacionInterno(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        string $tipoEvento,
        string $estadoEsperado,
        ?string $observacion = null
    ): void {
        $documentoActor = trim($documentoActor);
        DB::connection(self::CONNECTION)->transaction(function () use (
            $idNovedad,
            $nuevoEstado,
            $documentoActor,
            $tipoEvento,
            $estadoEsperado,
            $observacion
        ) {
            $novedad = DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->lockForUpdate()
                ->first(['id', 'id_origen', 'estado']);

            if (! $novedad) {
                throw new RuntimeException('NOVEDAD_NO_ENCONTRADA');
            }

            $estadoActual = trim((string) ($novedad->estado ?? ''));
            if ($estadoActual !== $estadoEsperado) {
                throw new RuntimeException('ESTADO_CAMBIO_CONCURRENTE');
            }

            $updateNovedad = [
                'estado' => $nuevoEstado,
                'fecha_modifica' => now(),
                'usuario_modifica' => $documentoActor !== '' ? $documentoActor : null,
            ];
            $updateDetalle = [
                'estado' => $nuevoEstado,
                'fecha_modifica' => now(),
            ];

            if ($observacion !== null) {
                $observacionLimpia = $this->limpiarObservacion($observacion);
                $updateNovedad['observacion'] = $observacionLimpia;
                $updateDetalle['observacion'] = $observacionLimpia;
            }

            DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->update($updateNovedad);

            DB::connection(self::CONNECTION)
                ->table('EMP_VACACIONES')
                ->where('id', (string) ($novedad->id_origen ?? ''))
                ->update($updateDetalle);

        });
    }

    private function actorPuedeAprobarInicial(string $documentoActor, object $vacacion): bool
    {
        $documentoActor = trim($documentoActor);
        if ($documentoActor === '') {
            return false;
        }

        $tipoAprobador = strtoupper(trim((string) ($vacacion->tipo_aprobador ?? 'JEFE')));
        if ($tipoAprobador === 'ASOCIADO') {
            $documentoAprobador = trim((string) ($vacacion->documento_aprobador ?? ''));

            return $documentoAprobador !== '' && $documentoActor === $documentoAprobador;
        }

        $documentoPersona = trim((string) ($vacacion->documento_persona ?? $vacacion->id_persona ?? ''));
        if ($documentoPersona === '') {
            return false;
        }

        return $this->jefeEquipoService->esJefeDirectoDeEmpleado($documentoActor, $documentoPersona);
    }

    private function resolverAprobadorInicial(string $documentoPersona): array
    {
        if (EmpleadoService::esConductorActivo($documentoPersona)) {
            $asociado = EmpleadoService::obtenerAsociadoDeConductor($documentoPersona);
            if (! is_array($asociado)) {
                return [
                    'ok' => false,
                    'message' => 'No se encontro un asociado activo para aprobar las vacaciones del conductor.',
                ];
            }

            return [
                'ok' => true,
                'tipo' => 'ASOCIADO',
                'documento' => trim((string) ($asociado['identificacion'] ?? '')),
                'nombre' => trim((string) ($asociado['nombre'] ?? '')),
                'correo' => trim((string) ($asociado['correo'] ?? '')),
            ];
        }

        $jefe = $this->jefeEquipoService->obtenerJefeDirectoDeEmpleado($documentoPersona);
        if (! is_array($jefe)) {
            return [
                'ok' => false,
                'message' => 'No se encontro jefe directo para aprobar la solicitud de vacaciones.',
            ];
        }

        return [
            'ok' => true,
            'tipo' => 'JEFE',
            'documento' => trim((string) ($jefe['identificacion'] ?? '')),
            'nombre' => trim((string) ($jefe['nombre'] ?? '')),
            'correo' => trim((string) ($jefe['correo'] ?? '')),
        ];
    }

    private function requiereAprobacionRrhh(string $documentoPersona): bool
    {
        $centroCosto = EmpleadoService::obtenerCentroCostoEmpleado($documentoPersona);
        $descripcion = strtoupper(trim((string) ($centroCosto['descripcion'] ?? '')));
        if ($descripcion === '') {
            return true;
        }

        return ! str_contains($descripcion, 'CONTRATO CERREJON');
    }

    private function limpiarObservacion(string $texto): ?string
    {
        $valor = trim($texto);
        if ($valor === '') {
            return null;
        }

        return mb_substr($valor, 0, self::MAX_OBSERVACION, 'UTF-8');
    }

    private function normalizarTexto(mixed $value): ?string
    {
        $texto = trim((string) $value);

        return $texto === '' ? null : $texto;
    }
}

