<?php

namespace App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes;

use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\GestionRRHH\Services\JefeEquipoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadPerfLogger;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class EmpleadoPermisoPermanenteService
{
    private const CONNECTION = 'oracle-360';
    private const MAX_OBSERVACION = 1000;
    private const JORNADA_MANANA = 'MANANA';
    private const JORNADA_TARDE = 'TARDE';
    private const JORNADA_AMBAS = 'AMBAS';
    private const JORNADAS_ENTRADA_MAP = [
        '1' => self::JORNADA_MANANA,
        '2' => self::JORNADA_TARDE,
        '1,2' => self::JORNADA_AMBAS,
        '1|2' => self::JORNADA_AMBAS,
        'MANANA' => self::JORNADA_MANANA,
        'MAÑANA' => self::JORNADA_MANANA,
        'TARDE' => self::JORNADA_TARDE,
        'AMBAS' => self::JORNADA_AMBAS,
    ];

    public const ESTADO_RADICADO = 'RADICADO';
    public const ESTADO_JEFE_APROBADO = 'JEFE_APROBADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_ANULADO = 'ANULADO';

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly JefeEquipoService $jefeEquipoService,
        private readonly EmpleadoPermisoPermanenteDocumentoService $documentoService
    ) {}

    public static function reglasCreacion(): array
    {
        return [
            'identificacion' => 'required|string|max:50',
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'jornada' => ['required', 'string', Rule::in(array_keys(self::JORNADAS_ENTRADA_MAP))],
            'horario_fijo' => 'nullable|boolean',
            'hora_salida_j1' => 'nullable|date_format:H:i',
            'hora_ingreso_j1' => 'nullable|date_format:H:i',
            'hora_salida_j2' => 'nullable|date_format:H:i',
            'hora_ingreso_j2' => 'nullable|date_format:H:i',
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
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser mayor o igual a la fecha de inicio.',
            'jornada.required' => 'Debes indicar la jornada del permiso permanente.',
            'jornada.in' => 'La jornada seleccionada no es valida.',
            'hora_salida_j1.date_format' => 'La hora salida de jornada 1 debe tener formato HH:MM.',
            'hora_ingreso_j1.date_format' => 'La hora ingreso de jornada 1 debe tener formato HH:MM.',
            'hora_salida_j2.date_format' => 'La hora salida de jornada 2 debe tener formato HH:MM.',
            'hora_ingreso_j2.date_format' => 'La hora ingreso de jornada 2 debe tener formato HH:MM.',
            'observacion.max' => 'La observacion no puede superar 1000 caracteres.',
        ];
    }

    public static function opcionesJornada(): array
    {
        return [
            self::JORNADA_MANANA => 'Manana (Jornada 1)',
            self::JORNADA_TARDE => 'Tarde (Jornada 2)',
            self::JORNADA_AMBAS => 'Ambas jornadas',
        ];
    }

    public static function normalizarJornada(?string $jornada): ?string
    {
        $valor = strtoupper(trim((string) $jornada));
        if ($valor === '') {
            return null;
        }

        $valor = str_replace([' ', ';'], ['', ','], $valor);

        return self::JORNADAS_ENTRADA_MAP[$valor] ?? null;
    }

    public function crearPermisoPermanente(
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        string $origen = 'WEB',
        ?string $ipEquipo = null,
        ?string $sistemaOrigen = null
    ): array {
        $perf = NovedadPerfLogger::start('radicar_permiso_permanente', [
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
                'message' => 'No se encontro el empleado activo para radicar el permiso permanente.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'buscar_persona');

        $documentoPersona = trim((string) ($persona['identificacion'] ?? ''));
        if ($documentoPersona === '') {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'documento_persona_vacio',
            ]);
            return [
                'ok' => false,
                'message' => 'No fue posible identificar el documento de la persona.',
                'http_status' => 422,
            ];
        }

        if (EmpleadoService::esConductorActivo($documentoPersona)) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'conductor_no_aplica',
            ]);
            return [
                'ok' => false,
                'message' => 'El permiso permanente aplica solo para personal administrativo.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'validaciones_iniciales');

        $jornada = self::normalizarJornada((string) ($payload['jornada'] ?? ''));
        if ($jornada === null) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'jornada_invalida',
            ]);
            return [
                'ok' => false,
                'message' => 'La jornada seleccionada no es valida.',
                'http_status' => 422,
            ];
        }

        $horarioFijo = filter_var($payload['horario_fijo'] ?? false, FILTER_VALIDATE_BOOL);
        $validaHorario = $this->validarHorarioPorJornada($payload, $jornada, $horarioFijo);
        if (! ($validaHorario['ok'] ?? false)) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'horario_invalido',
            ]);
            return [
                'ok' => false,
                'message' => (string) ($validaHorario['message'] ?? 'No fue posible validar el horario del permiso permanente.'),
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'validar_horario');

        $fechaInicio = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_inicio'])->startOfDay();
        $fechaFin = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_fin'])->endOfDay();

        if ($this->existeCruceVigente($documentoPersona, $fechaInicio, $fechaFin)) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'cruce_vigente',
            ]);
            return [
                'ok' => false,
                'message' => 'Ya existe un permiso permanente activo o en proceso que se cruza con el rango de fechas indicado.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'validar_cruce_vigente');

        $tipoNovedadId = $this->documentoService->obtenerTipoNovedadPermisoPermanenteId();
        if ($tipoNovedadId === null) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'tipo_novedad_no_encontrado',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el tipo de novedad PERMISO_PERMANENTE en EMP_NOVEDADES_TIPO.',
                'http_status' => 500,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_tipo_novedad');

        $jefeDirecto = $this->jefeEquipoService->obtenerJefeDirectoDeEmpleado($documentoPersona);
        if (! is_array($jefeDirecto)) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'jefe_no_encontrado',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro jefe directo para aprobar el permiso permanente.',
                'http_status' => 422,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_jefe');

        $idNovedad = (string) Str::uuid();
        $idDetalle = (string) Str::uuid();
        $observacion = $this->limpiarObservacion((string) ($payload['observacion'] ?? ''));
        $documentoActor = trim($documentoActor);

        try {
            DB::connection(self::CONNECTION)->transaction(function () use (
                $idNovedad,
                $idDetalle,
                $documentoPersona,
                $documentoActor,
                $fechaInicio,
                $fechaFin,
                $jornada,
                $horarioFijo,
                $payload,
                $observacion,
                $tipoNovedadId,
                $origen,
                $ipEquipo,
                $sistemaOrigen,
                $jefeDirecto
            ) {
                DB::connection(self::CONNECTION)
                    ->table('EMP_PERMISOS_PERMANENTES')
                    ->insert([
                        'id' => $idDetalle,
                        'documento_persona' => $documentoPersona,
                        'documento_radica' => $documentoActor !== '' ? $documentoActor : $documentoPersona,
                        'fecha_inicio' => $fechaInicio->toDateString(),
                        'fecha_fin' => $fechaFin->toDateString(),
                        'jornada' => $jornada,
                        'horario_fijo' => $horarioFijo ? 1 : 0,
                        'hora_salida_j1' => $this->normalizarHora($payload['hora_salida_j1'] ?? null),
                        'hora_ingreso_j1' => $this->normalizarHora($payload['hora_ingreso_j1'] ?? null),
                        'hora_salida_j2' => $this->normalizarHora($payload['hora_salida_j2'] ?? null),
                        'hora_ingreso_j2' => $this->normalizarHora($payload['hora_ingreso_j2'] ?? null),
                        'observacion' => $observacion,
                        'estado' => self::ESTADO_RADICADO,
                        'origen' => strtoupper(trim($origen)),
                        'ip_equipo' => $this->normalizarTexto($ipEquipo),
                        'sistema_origen' => $this->normalizarTexto($sistemaOrigen),
                        'documento_aprobador' => trim((string) ($jefeDirecto['identificacion'] ?? '')),
                        'nombre_aprobador' => trim((string) ($jefeDirecto['nombre'] ?? '')),
                        'correo_aprobador' => trim((string) ($jefeDirecto['correo'] ?? '')),
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
                'message' => 'No fue posible radicar el permiso permanente.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }

        $respuesta = [
            'ok' => true,
            'message' => 'Permiso permanente radicado correctamente.',
            'id_novedad' => $idNovedad,
            'id_detalle' => $idDetalle,
            'estado' => self::ESTADO_RADICADO,
            'jefe_directo' => [
                'identificacion' => trim((string) ($jefeDirecto['identificacion'] ?? '')),
                'nombre' => trim((string) ($jefeDirecto['nombre'] ?? '')),
                'correo' => trim((string) ($jefeDirecto['correo'] ?? '')),
            ],
        ];
        NovedadPerfLogger::finish($perf, [
            'ok' => true,
            'id_novedad' => $idNovedad,
        ]);

        return $respuesta;
    }

    public function aprobarPorJefe(string $idNovedad, string $documentoActor, bool $esSuperAdmin = false): array
    {
        $permiso = $this->obtenerNovedadPermisoPermanente($idNovedad);
        if (! $permiso) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de permiso permanente.'];
        }

        if ((string) $permiso->estado !== self::ESTADO_RADICADO) {
            return ['ok' => false, 'message' => 'La solicitud no esta pendiente de aprobacion inicial.'];
        }

        $documentoActor = trim($documentoActor);
        $documentoPersona = trim((string) ($permiso->documento_persona ?? $permiso->id_persona ?? ''));
        $puede = $esSuperAdmin || (
            $documentoActor !== ''
            && $documentoPersona !== ''
            && $this->jefeEquipoService->esJefeDirectoDeEmpleado($documentoActor, $documentoPersona)
        );
        if (! $puede) {
            return ['ok' => false, 'message' => 'No tienes autorizacion para aprobar este permiso permanente.'];
        }

        return $this->cambiarEstado(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_JEFE_APROBADO,
            documentoActor: $documentoActor,
            tipoEvento: 'APROBACION_JEFE',
            estadoEsperado: self::ESTADO_RADICADO,
            mensajeOk: 'Permiso permanente aprobado por jefe. Pendiente gestion de RRHH.'
        );
    }

    public function aprobarPorRrhh(string $idNovedad, string $documentoActor, bool $esRrhh, bool $esSuperAdmin = false): array
    {
        $permiso = $this->obtenerNovedadPermisoPermanente($idNovedad);
        if (! $permiso) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de permiso permanente.'];
        }

        if ((string) $permiso->estado !== self::ESTADO_JEFE_APROBADO) {
            return ['ok' => false, 'message' => 'La solicitud no esta pendiente de aprobacion de RRHH.'];
        }

        if (! $esSuperAdmin && ! $esRrhh) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para aprobar esta solicitud.'];
        }

        return $this->cambiarEstado(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_APROBADO,
            documentoActor: $documentoActor,
            tipoEvento: 'APROBACION_RRHH',
            estadoEsperado: self::ESTADO_JEFE_APROBADO,
            mensajeOk: 'Permiso permanente aprobado por RRHH correctamente.'
        );
    }

    public function rechazarPermisoPermanente(
        string $idNovedad,
        string $nivel,
        string $motivoRechazo,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $permiso = $this->obtenerNovedadPermisoPermanente($idNovedad);
        if (! $permiso) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de permiso permanente.'];
        }

        $nivel = strtolower(trim($nivel));
        $motivoRechazo = trim($motivoRechazo);
        if ($motivoRechazo === '') {
            return ['ok' => false, 'message' => 'Debes indicar el motivo del rechazo.'];
        }

        $documentoActor = trim($documentoActor);
        $documentoPersona = trim((string) ($permiso->documento_persona ?? $permiso->id_persona ?? ''));
        if ($nivel === 'jefe') {
            if ((string) $permiso->estado !== self::ESTADO_RADICADO) {
                return ['ok' => false, 'message' => 'La solicitud no esta pendiente de rechazo en nivel jefe.'];
            }

            $puede = $esSuperAdmin || (
                $documentoActor !== ''
                && $documentoPersona !== ''
                && $this->jefeEquipoService->esJefeDirectoDeEmpleado($documentoActor, $documentoPersona)
            );
            if (! $puede) {
                return ['ok' => false, 'message' => 'No tienes autorizacion para rechazar esta solicitud.'];
            }
        } elseif ($nivel === 'rrhh') {
            if ((string) $permiso->estado !== self::ESTADO_JEFE_APROBADO) {
                return ['ok' => false, 'message' => 'La solicitud no esta pendiente de rechazo por RRHH.'];
            }
            if (! $esSuperAdmin && ! $esRrhh) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para rechazar esta solicitud.'];
            }
        } else {
            return ['ok' => false, 'message' => 'El nivel del rechazo no es valido.'];
        }

        return $this->cambiarEstado(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_RECHAZADO,
            documentoActor: $documentoActor,
            tipoEvento: 'RECHAZO',
            estadoEsperado: (string) $permiso->estado,
            observacion: $motivoRechazo,
            mensajeOk: 'Permiso permanente rechazado correctamente.'
        );
    }

    public function anularPermisoPermanente(
        string $idNovedad,
        string $motivoAnulacion,
        string $documentoActor,
        bool $esSuperAdmin = false
    ): array {
        $permiso = $this->obtenerNovedadPermisoPermanente($idNovedad);
        if (! $permiso) {
            return ['ok' => false, 'message' => 'No se encontro la solicitud de permiso permanente.'];
        }

        $motivoAnulacion = trim($motivoAnulacion);
        if ($motivoAnulacion === '') {
            return ['ok' => false, 'message' => 'Debes indicar la razon de la anulacion.'];
        }

        $documentoActor = trim($documentoActor);
        $documentoPersona = trim((string) ($permiso->documento_persona ?? $permiso->id_persona ?? ''));
        if (! $esSuperAdmin && $documentoActor !== $documentoPersona) {
            return ['ok' => false, 'message' => 'Solo la persona titular de la solicitud puede anular el permiso permanente.'];
        }

        if (in_array((string) $permiso->estado, [self::ESTADO_APROBADO, self::ESTADO_RECHAZADO, self::ESTADO_ANULADO], true)) {
            return ['ok' => false, 'message' => 'La solicitud no puede anularse desde el estado actual.'];
        }

        return $this->cambiarEstado(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_ANULADO,
            documentoActor: $documentoActor,
            tipoEvento: 'ANULACION',
            estadoEsperado: (string) $permiso->estado,
            observacion: $motivoAnulacion,
            mensajeOk: 'Permiso permanente anulado correctamente.'
        );
    }

    public function obtenerNovedadPermisoPermanente(string $idNovedad): ?object
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_PERMISOS_PERMANENTES as p', 'p.id', '=', 'n.id_origen')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->where('n.id', $idNovedad)
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(t.tabla, '')) = ?", ['EMP_PERMISOS_PERMANENTES'])
                    ->orWhereRaw("UPPER(NVL(t.descripcion, '')) = ?", ['PERMISO_PERMANENTE'])
                    ->orWhereRaw("UPPER(NVL(t.descripcion, '')) = ?", ['PERMISO PERMANENTE']);
            })
            ->select([
                'n.id',
                'n.id_persona',
                'n.id_origen',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'p.documento_persona',
                'p.documento_radica',
                'p.fecha_inicio as fecha_inicio_permiso',
                'p.fecha_fin as fecha_fin_permiso',
                'p.jornada',
                'p.horario_fijo',
                'p.hora_salida_j1',
                'p.hora_ingreso_j1',
                'p.hora_salida_j2',
                'p.hora_ingreso_j2',
                'p.documento_aprobador',
                'p.nombre_aprobador',
                'p.correo_aprobador',
            ])
            ->first();
    }

    public function obtenerDetalleCorreo(string $idNovedad): ?array
    {
        $permiso = $this->obtenerNovedadPermisoPermanente($idNovedad);
        if (! $permiso) {
            return null;
        }

        $persona = $this->permisoService->buscarPersona((string) ($permiso->documento_persona ?? $permiso->id_persona ?? ''));

        return [
            'permiso' => $permiso,
            'persona' => $persona,
        ];
    }

    private function validarHorarioPorJornada(array $payload, string $jornada, bool $horarioFijo): array
    {
        if (! $horarioFijo) {
            return ['ok' => true];
        }

        $incluyeJornada1 = in_array($jornada, [self::JORNADA_MANANA, self::JORNADA_AMBAS], true);
        $incluyeJornada2 = in_array($jornada, [self::JORNADA_TARDE, self::JORNADA_AMBAS], true);

        $sJ1 = trim((string) ($payload['hora_salida_j1'] ?? ''));
        $iJ1 = trim((string) ($payload['hora_ingreso_j1'] ?? ''));
        $sJ2 = trim((string) ($payload['hora_salida_j2'] ?? ''));
        $iJ2 = trim((string) ($payload['hora_ingreso_j2'] ?? ''));

        if ($incluyeJornada1 && ($sJ1 === '' || $iJ1 === '')) {
            return ['ok' => false, 'message' => 'Debes registrar hora salida e ingreso para jornada 1 cuando defines horario fijo.'];
        }

        if ($incluyeJornada2 && ($sJ2 === '' || $iJ2 === '')) {
            return ['ok' => false, 'message' => 'Debes registrar hora salida e ingreso para jornada 2 cuando defines horario fijo.'];
        }

        return ['ok' => true];
    }

    private function existeCruceVigente(string $documentoPersona, Carbon $fechaInicio, Carbon $fechaFin): bool
    {
        return DB::connection(self::CONNECTION)
            ->table('EMP_PERMISOS_PERMANENTES')
            ->where('documento_persona', $documentoPersona)
            ->whereIn('estado', [
                self::ESTADO_RADICADO,
                self::ESTADO_JEFE_APROBADO,
                self::ESTADO_APROBADO,
            ])
            ->whereDate('fecha_inicio', '<=', $fechaFin->toDateString())
            ->whereDate('fecha_fin', '>=', $fechaInicio->toDateString())
            ->exists();
    }

    private function cambiarEstado(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        string $tipoEvento,
        string $estadoEsperado,
        ?string $observacion = null,
        ?string $mensajeOk = null
    ): array {
        try {
            $this->cambiarEstadoInterno(
                idNovedad: $idNovedad,
                nuevoEstado: $nuevoEstado,
                documentoActor: $documentoActor,
                tipoEvento: $tipoEvento,
                estadoEsperado: $estadoEsperado,
                observacion: $observacion
            );

            return [
                'ok' => true,
                'message' => $mensajeOk ?? 'Estado del permiso permanente actualizado correctamente.',
                'estado' => $nuevoEstado,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible actualizar el estado de la solicitud de permiso permanente.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function cambiarEstadoInterno(
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
                ->table('EMP_PERMISOS_PERMANENTES')
                ->where('id', (string) ($novedad->id_origen ?? ''))
                ->update($updateDetalle);

        });
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

    private function normalizarHora(mixed $value): ?string
    {
        $hora = trim((string) $value);

        return $hora === '' ? null : $hora;
    }
}
