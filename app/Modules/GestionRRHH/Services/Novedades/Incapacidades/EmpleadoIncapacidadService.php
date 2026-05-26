<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Incapacidades;

use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadPerfLogger;
use App\Services\DocumentalStorageService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoIncapacidadService
{
    private const CONNECTION = 'oracle-360';
    private const CACHE_TTL_SECONDS = 900;

    public const ESTADO_RADICADO = 'RADICADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_ANULADO = 'ANULADO';

    private const TIPOS_INCAPACIDAD = [
        'INICIO' => 'INICIO',
        'PRORROGA' => 'PRORROGA',
    ];

    public function __construct(
        private readonly EmpleadoIncapacidadDocumentoService $documentoService,
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public static function reglasCreacion(): array
    {
        return [
            'identificacion' => 'required|string|max:50',
            'causa_id' => 'required|string|max:100',
            'diagnostico_id' => 'required|string|max:100',
            'eps_id' => 'required|string|max:100',
            'arl_id' => 'required|string|max:100',
            'tipo_incapacidad' => 'required|string|in:'.implode(',', array_keys(self::TIPOS_INCAPACIDAD)),
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'observacion' => 'nullable|string|max:255',
        ];
    }

    public static function mensajesCreacion(): array
    {
        return [
            'identificacion.required' => 'La identificacion del empleado es obligatoria.',
            'causa_id.required' => 'Debe seleccionar la causa de la incapacidad.',
            'diagnostico_id.required' => 'Debe seleccionar el codigo de diagnostico.',
            'eps_id.required' => 'Debe seleccionar la EPS.',
            'arl_id.required' => 'Debe seleccionar la ARL.',
            'tipo_incapacidad.required' => 'Debe seleccionar el tipo de incapacidad.',
            'tipo_incapacidad.in' => 'El tipo de incapacidad seleccionado no es valido.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener formato YYYY-MM-DD.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'observacion.max' => 'La observacion no puede exceder 255 caracteres.',
        ];
    }

    public static function opcionesTipoIncapacidad(): array
    {
        return self::TIPOS_INCAPACIDAD;
    }

    public static function opcionesEstado(): array
    {
        return [
            self::ESTADO_RADICADO => 'Radicado',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_ANULADO => 'Anulado',
        ];
    }

    public function radicarIncapacidad(
        array $payload,
        array $adjuntos,
        string $documentoActor,
        ?string $nombreActor,
        string $origen = 'WEB',
        ?string $ipEquipo = null,
        ?string $sistemaOrigen = null
    ): array {
        $perf = NovedadPerfLogger::start('radicar_incapacidad', [
            'origen' => strtoupper(trim($origen)),
            'documento_actor' => trim($documentoActor),
            'identificacion' => trim((string) ($payload['identificacion'] ?? '')),
            'adjuntos_count' => is_array($adjuntos) ? count($adjuntos) : 0,
        ]);

        $payload = $this->normalizarPayload($payload);
        NovedadPerfLogger::checkpoint($perf, 'normalizar_payload');
        $persona = $this->buscarPersona((string) $payload['identificacion']);
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

        try {
            $errorCatalogo = $this->validarCatalogos($payload);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible validar los catalogos de incapacidad.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'validar_catalogos');

        if ($errorCatalogo !== null) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'catalogo_invalido',
            ]);
            return [
                'ok' => false,
                'message' => $errorCatalogo,
                'http_status' => 422,
            ];
        }

        $tipoIncapacidadId = $this->obtenerTipoIncapacidadId();
        if ($tipoIncapacidadId === null) {
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'motivo' => 'tipo_novedad_no_encontrado',
            ]);
            return [
                'ok' => false,
                'message' => 'No se encontro el tipo de novedad Incapacidad en Oracle 360.',
                'http_status' => 500,
            ];
        }
        NovedadPerfLogger::checkpoint($perf, 'resolver_tipo_novedad');

        $connection = DB::connection(self::CONNECTION);
        $resultadoAdjuntos = null;

        $connection->beginTransaction();

        try {
            $idDetalle = (string) Str::uuid();
            $idNovedad = (string) Str::uuid();
            $fechaCreacion = now();
            $documentoPersona = trim((string) $persona['identificacion']);
            $documentoActor = $this->normalizarDocumentoActor($documentoActor, $documentoPersona);

            $connection->table('EMP_INCAPACIDADES')->insert([
                'id' => $idDetalle,
                'documento_persona' => $documentoPersona !== '' ? $documentoPersona : null,
                'documento_radica' => $documentoActor !== '' ? $documentoActor : $documentoPersona,
                'id_causa_incapacidad' => $payload['causa_id'],
                'id_diagnostico' => $payload['diagnostico_id'],
                'id_eps' => $payload['eps_id'],
                'id_arl' => $payload['arl_id'],
                'tipo_incapacidad' => $payload['tipo_incapacidad'],
                'estado' => self::ESTADO_RADICADO,
                'observacion' => $this->normalizarTexto($payload['observacion'] ?? null),
                'fecha_creacion' => $fechaCreacion,
                'fecha_modifica' => $fechaCreacion,
            ]);

            $connection->table('EMP_NOVEDADES')->insert([
                'id' => $idNovedad,
                'id_persona' => $documentoPersona !== '' ? $documentoPersona : null,
                'id_tipo_novedad' => $tipoIncapacidadId,
                'id_origen' => $idDetalle,
                'estado' => self::ESTADO_RADICADO,
                'fecha_inicio' => $payload['fecha_inicio'],
                'fecha_fin' => $payload['fecha_fin'],
                'fecha_creacion' => $fechaCreacion,
                'usuario_creacion' => $documentoActor !== '' ? $documentoActor : $documentoPersona,
                'observacion' => $this->normalizarTexto($payload['observacion'] ?? null),
            ]);

            $resultadoAdjuntos = $this->documentoService->guardarAdjuntos(
                idNovedad: $idNovedad,
                adjuntos: $adjuntos
            );
            NovedadPerfLogger::checkpoint($perf, 'guardar_adjuntos', [
                'metricas' => (array) ($resultadoAdjuntos['metricas'] ?? []),
            ]);

            if (! ($resultadoAdjuntos['ok'] ?? false)) {
                $messageAdjuntos = (string) ($resultadoAdjuntos['message'] ?? 'No fue posible guardar los adjuntos.');
                $detalleAdjuntos = trim((string) ($resultadoAdjuntos['error'] ?? ''));
                if ($detalleAdjuntos !== '') {
                    $messageAdjuntos .= ' '.$detalleAdjuntos;
                }

                throw new RuntimeException($messageAdjuntos);
            }

            $connection->commit();
            NovedadPerfLogger::checkpoint($perf, 'commit_transaccion');

            $respuesta = [
                'ok' => true,
                'message' => 'Incapacidad radicada correctamente.',
                'id_detalle' => $idDetalle,
                'id_novedad' => $idNovedad,
                'estado' => self::ESTADO_RADICADO,
                'adjuntos' => $resultadoAdjuntos,
            ];
            NovedadPerfLogger::finish($perf, [
                'ok' => true,
                'id_novedad' => $idNovedad,
            ]);

            return $respuesta;
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->limpiarAdjuntosFallidos($resultadoAdjuntos);
            NovedadPerfLogger::finish($perf, [
                'ok' => false,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'No fue posible radicar la incapacidad.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }
    }

    public function obtenerDetalleGestion(string $idNovedad): ?object
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        $detalle = $this->nuevaConsultaBase()
            ->where('n.id', $idNovedad)
            ->first();

        if (! $detalle) {
            return null;
        }

        $persona = $this->buscarPersona(trim((string) ($detalle->documento_persona ?? $detalle->id_persona ?? '')));
        $detalle->persona = $persona;
        $detalle->dias_incapacidad = $this->calcularDiasIncapacidad($detalle->fecha_inicio ?? null, $detalle->fecha_fin ?? null);

        return $detalle;
    }

    public function actualizarSolicitud(string $idNovedad, array $payload, string $documentoActor): array
    {
        $detalle = $this->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return [
                'ok' => false,
                'message' => 'No se encontro la solicitud de incapacidad.',
            ];
        }

        $payload = $this->normalizarPayload(array_merge([
            'identificacion' => (string) ($detalle->documento_persona ?? $detalle->id_persona ?? ''),
        ], $payload));

        $errorCatalogo = $this->validarCatalogos($payload);
        if ($errorCatalogo !== null) {
            return [
                'ok' => false,
                'message' => $errorCatalogo,
            ];
        }

        $connection = DB::connection(self::CONNECTION);
        $connection->beginTransaction();

        try {
            $connection->table('EMP_INCAPACIDADES')
                ->where('id', (string) $detalle->id_origen)
                ->update([
                    'id_causa_incapacidad' => $payload['causa_id'],
                    'id_diagnostico' => $payload['diagnostico_id'],
                    'id_eps' => $payload['eps_id'],
                    'id_arl' => $payload['arl_id'],
                    'tipo_incapacidad' => $payload['tipo_incapacidad'],
                    'observacion' => $this->normalizarTexto($payload['observacion'] ?? null),
                    'fecha_modifica' => now(),
                ]);

            $connection->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->update([
                    'fecha_inicio' => $payload['fecha_inicio'],
                    'fecha_fin' => $payload['fecha_fin'],
                    'observacion' => $this->normalizarTexto($payload['observacion'] ?? null),
                    'fecha_modifica' => now(),
                    'usuario_modifica' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                ]);

            $connection->commit();

            return [
                'ok' => true,
                'message' => 'La solicitud de incapacidad fue actualizada correctamente.',
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();

            return [
                'ok' => false,
                'message' => 'No fue posible actualizar la solicitud de incapacidad.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function obtenerSeguimientos(string $idNovedad): array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return [];
        }

        $items = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->where('id_novedad', $idNovedad)
            ->where('tipo_evento', 'SEGUIMIENTO_RRHH')
            ->orderByDesc('fecha_cambio')
            ->get([
                'id',
                'fecha_cambio',
                'usuario_accion',
                'observacion',
            ]);

        return $items->map(function ($item) {
            $documento = trim((string) ($item->usuario_accion ?? ''));
            $nombre = '';
            if ($documento !== '') {
                $persona = $this->buscarPersona($documento);
                $nombre = trim((string) ($persona['nombre'] ?? ''));
            }

            return (object) [
                'id' => trim((string) ($item->id ?? '')),
                'fecha_cambio' => $item->fecha_cambio,
                'documento_actor' => $documento,
                'nombre_actor' => $nombre,
                'observacion' => trim((string) ($item->observacion ?? '')),
            ];
        })->all();
    }

    public function agregarSeguimiento(string $idNovedad, string $documentoActor, string $observacion): array
    {
        $detalle = $this->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return [
                'ok' => false,
                'message' => 'No se encontro la solicitud de incapacidad.',
            ];
        }

        $estadoActual = strtoupper(trim((string) ($detalle->estado ?? '')));
        if ($estadoActual !== self::ESTADO_APROBADO) {
            return [
                'ok' => false,
                'message' => 'Solo se puede registrar seguimiento para incapacidades en estado APROBADO.',
            ];
        }

        $observacion = trim($observacion);
        if ($observacion === '') {
            return [
                'ok' => false,
                'message' => 'Debes registrar una observacion de seguimiento.',
            ];
        }

        try {
            DB::connection(self::CONNECTION)->transaction(function () use ($idNovedad, $detalle, $documentoActor, $observacion) {
                DB::connection(self::CONNECTION)
                    ->table('EMP_NOVEDADES')
                    ->where('id', $idNovedad)
                    ->update([
                        'fecha_modifica' => now(),
                        'usuario_modifica' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                    ]);

                DB::connection(self::CONNECTION)
                    ->table('EMP_INCAPACIDADES')
                    ->where('id', (string) $detalle->id_origen)
                    ->update([
                        'fecha_modifica' => now(),
                    ]);

                $this->registrarHistorial(
                    idNovedad: $idNovedad,
                    estadoAnterior: trim((string) ($detalle->estado ?? '')),
                    estadoNuevo: trim((string) ($detalle->estado ?? '')),
                    documentoActor: trim($documentoActor),
                    tipoEvento: 'SEGUIMIENTO_RRHH',
                    observacion: $this->normalizarTexto($observacion)
                );
            });

            return [
                'ok' => true,
                'message' => 'Seguimiento registrado correctamente.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible registrar el seguimiento de la incapacidad.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function aprobarSolicitud(string $idNovedad, string $documentoActor, BloqueoService $bloqueoService): array
    {
        $detalle = $this->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return [
                'ok' => false,
                'message' => 'No se encontro la solicitud de incapacidad.',
            ];
        }

        $estadoActual = strtoupper(trim((string) ($detalle->estado ?? '')));
        if ($estadoActual !== self::ESTADO_RADICADO) {
            return [
                'ok' => false,
                'message' => 'Solo se pueden aprobar solicitudes en estado RADICADO.',
            ];
        }

        $resultadoBloqueo = $this->generarBloqueoSiAplica($detalle, $bloqueoService);
        if ($resultadoBloqueo === 'error') {
            return [
                'ok' => false,
                'message' => 'Error al generar el bloqueo de la novedad aprobada, por favor verifica la informacion de la incapacidad.',
            ];
        }

        $resultado = $this->cambiarEstadoSolicitud(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_APROBADO,
            documentoActor: $documentoActor,
            observacion: null,
            tipoEvento: 'APROBACION_RRHH'
        );

        if (! ($resultado['ok'] ?? false)) {
            return $resultado;
        }

        $resultado['message'] = $resultadoBloqueo === 'bloqueado'
            ? 'La incapacidad fue aprobada y se genero el bloqueo asociado.'
            : 'La incapacidad fue aprobada correctamente.';

        return $resultado;
    }

    public function rechazarSolicitud(string $idNovedad, string $documentoActor, string $motivo): array
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            return [
                'ok' => false,
                'message' => 'El motivo del rechazo es obligatorio.',
            ];
        }

        return $this->cambiarEstadoSolicitud(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_RECHAZADO,
            documentoActor: $documentoActor,
            observacion: $motivo,
            tipoEvento: 'RECHAZO'
        );
    }

    public function anularSolicitud(
        string $idNovedad,
        string $documentoActor,
        string $motivoAnulacion,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $motivoAnulacion = trim($motivoAnulacion);
        if ($motivoAnulacion === '') {
            return [
                'ok' => false,
                'message' => 'Debe indicar la razon de la anulacion.',
            ];
        }

        $detalle = $this->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return [
                'ok' => false,
                'message' => 'No se encontro la solicitud de incapacidad.',
            ];
        }

        $estadoActual = strtoupper(trim((string) ($detalle->estado ?? '')));
        if (in_array($estadoActual, [self::ESTADO_APROBADO, self::ESTADO_RECHAZADO, self::ESTADO_ANULADO], true)) {
            return [
                'ok' => false,
                'message' => 'La incapacidad no puede anularse desde el estado actual.',
            ];
        }

        $documentoActor = trim($documentoActor);
        $documentoPersona = trim((string) ($detalle->documento_persona ?? $detalle->id_persona ?? ''));
        $documentoRadica = trim((string) ($detalle->documento_radica ?? ''));
        $esTitular = $documentoActor !== '' && $documentoActor === $documentoPersona;
        $esRadicador = $documentoActor !== '' && $documentoActor === $documentoRadica;

        if (! $esSuperAdmin && ! $esRrhh && ! $esTitular && ! $esRadicador) {
            return [
                'ok' => false,
                'message' => 'No tienes autorizacion para anular esta incapacidad.',
            ];
        }

        return $this->cambiarEstadoSolicitud(
            idNovedad: $idNovedad,
            nuevoEstado: self::ESTADO_ANULADO,
            documentoActor: $documentoActor,
            observacion: $motivoAnulacion,
            tipoEvento: 'ANULACION'
        );
    }

    public function buscarPersona(string $identificacion): ?array
    {
        $identificacion = trim($identificacion);
        if ($identificacion === '') {
            return null;
        }

        return Cache::remember('per_persona_incapacidad_'.$identificacion, 300, function () use ($identificacion) {
            $row = DB::connection('oracle')
                ->table('per_personas as p')
                ->leftJoin('per_empresapersonas as ep', function ($join) {
                    $join->on('ep.pe_id_pe', '=', 'p.id')
                        ->whereIn('ep.tp_id', [1, 6, 11, 12])
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
                    "p.id as id,
                    p.identificacion as identificacion,
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
                'id' => (int) ($row->id ?? 0),
                'identificacion' => trim((string) ($row->identificacion ?? '')),
                'nombre' => mb_strtoupper(trim((string) ($row->nombre ?? '')), 'UTF-8'),
                'codigo' => trim((string) ($row->codigo ?? '')),
                'sexo' => trim((string) ($row->sexo ?? '')),
                'edad' => isset($row->edad) ? (string) $row->edad : '',
                'seccion' => trim((string) ($row->seccion ?? '')),
            ];
        });
    }

    public function obtenerCausas(): array
    {
        return Cache::remember('empleados_incapacidades_causas_oracle', self::CACHE_TTL_SECONDS, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_CAUSAS_INCAPACIDAD')
                ->where('estado', 'ACTIVO')
                ->orderBy('causa')
                ->get(['id', 'causa'])
                ->mapWithKeys(fn ($item) => [trim((string) $item->id) => trim((string) $item->causa)])
                ->all();
        });
    }

    public function obtenerDiagnosticos(): array
    {
        return Cache::remember('empleados_incapacidades_diagnosticos_oracle', self::CACHE_TTL_SECONDS, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_DIAGNOSTICOS')
                ->where('estado', 'ACTIVO')
                ->orderBy('CodigoCie')
                ->get(['id', 'CodigoCie', 'DescCie'])
                ->mapWithKeys(fn ($item) => [trim((string) $item->id) => $this->formatearDiagnosticoLabel($item)])
                ->all();
        });
    }

    public function obtenerDiagnosticosIniciales(int $limite = 10): array
    {
        $limite = max(1, min($limite, 50));

        return DB::connection(self::CONNECTION)
            ->table('EMP_DIAGNOSTICOS')
            ->where('estado', 'ACTIVO')
            ->orderBy('CodigoCie')
            ->limit($limite)
            ->get([
                'id',
                DB::raw('CodigoCie as codigo_cie'),
                DB::raw('DescCie as desc_cie'),
            ])
            ->mapWithKeys(fn ($item) => [trim((string) $item->id) => $this->formatearDiagnosticoLabel($item)])
            ->all();
    }

    public function obtenerDiagnosticoPorId(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $diagnostico = DB::connection(self::CONNECTION)
            ->table('EMP_DIAGNOSTICOS')
            ->where('id', $id)
            ->where('estado', 'ACTIVO')
            ->first([
                'id',
                DB::raw('CodigoCie as codigo_cie'),
                DB::raw('DescCie as desc_cie'),
            ]);

        if (! $diagnostico) {
            return null;
        }

        return [
            'id' => trim((string) $diagnostico->id),
            'text' => $this->formatearDiagnosticoLabel($diagnostico),
        ];
    }

    public function buscarDiagnosticos(string $termino = '', int $limite = 10): array
    {
        $limite = max(1, min($limite, 50));
        $termino = trim($termino);

        $query = DB::connection(self::CONNECTION)
            ->table('EMP_DIAGNOSTICOS')
            ->where('estado', 'ACTIVO')
            ->select([
                'id',
                DB::raw('CodigoCie as codigo_cie'),
                DB::raw('DescCie as desc_cie'),
            ])
            ->orderBy('CodigoCie');

        if ($termino !== '') {
            $query->where(function ($subquery) use ($termino) {
                $subquery->where('CodigoCie', 'like', '%'.$termino.'%')
                    ->orWhere('DescCie', 'like', '%'.$termino.'%');
            });
        }

        return $query
            ->limit($limite)
            ->get()
            ->map(fn ($item) => [
                'id' => trim((string) $item->id),
                'text' => $this->formatearDiagnosticoLabel($item),
            ])
            ->values()
            ->all();
    }

    public function obtenerEps(): array
    {
        return Cache::remember('empleados_incapacidades_eps_oracle', self::CACHE_TTL_SECONDS, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_EPS')
                ->where('estado', 'ACTIVO')
                ->orderBy('EPSNombre')
                ->get([
                    'id',
                    DB::raw('EPSNombre as eps_nombre'),
                ])
                ->mapWithKeys(function ($item) {
                    $id = trim((string) ($item->id ?? ''));
                    $nombre = trim((string) ($item->eps_nombre ?? ''));

                    if ($id === '' || $nombre === '') {
                        return [];
                    }

                    return [$id => $nombre];
                })
                ->all();
        });
    }

    public function obtenerArl(): array
    {
        return Cache::remember('empleados_incapacidades_arl_oracle', self::CACHE_TTL_SECONDS, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_ARL')
                ->where('estado', 'ACTIVO')
                ->orderBy('ARLNombre')
                ->get([
                    'id',
                    DB::raw('ARLNombre as arl_nombre'),
                ])
                ->mapWithKeys(function ($item) {
                    $id = trim((string) ($item->id ?? ''));
                    $nombre = trim((string) ($item->arl_nombre ?? ''));

                    if ($id === '' || $nombre === '') {
                        return [];
                    }

                    return [$id => $nombre];
                })
                ->all();
        });
    }

    public function obtenerIncapacidadesPaginadas(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));
        $documento = trim((string) ($filtros['persona_exacta'] ?? ''));
        $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));

        $query = $this->nuevaConsultaBase();

        if ($documento !== '') {
            $query->where('n.id_persona', $documento);
        }

        if ($estado !== '' && isset(self::opcionesEstado()[$estado])) {
            $query->where('n.estado', $estado);
        }

        return $query
            ->orderByDesc('n.fecha_creacion')
            ->paginate($perPage);
    }

    public function catalogos(): array
    {
        return [
            'causas' => $this->obtenerCausas(),
            'diagnosticos' => $this->obtenerDiagnosticosIniciales(20),
            'eps' => $this->obtenerEps(),
            'arl' => $this->obtenerArl(),
            'tipos_incapacidad' => self::opcionesTipoIncapacidad(),
            'tipos_documento' => $this->documentoService->obtenerTiposDocumento(),
            'tipos_documento_por_grupo' => [
                'DOCUMENTOS-INCAPACIDAD' => $this->documentoService->obtenerTiposDocumentoPorGrupo('DOCUMENTOS-INCAPACIDAD'),
                'DOCUMENTOS-INCAPACIDAD-MP' => $this->documentoService->obtenerTiposDocumentoPorGrupo('DOCUMENTOS-INCAPACIDAD-MP'),
                'DOCUMENTOS-INCAPACIDAD-AT' => $this->documentoService->obtenerTiposDocumentoPorGrupo('DOCUMENTOS-INCAPACIDAD-AT'),
            ],
            'documentos_por_causa' => $this->obtenerDocumentosPorCausa(),
        ];
    }

    public function obtenerDocumentosPorCausa(): array
    {
        $resultado = [];

        foreach ($this->obtenerCausas() as $id => $nombre) {
            $documentos = $this->documentoService->obtenerTiposDocumentoPorCausa((string) $id);
            $resultado[(string) $id] = [
                'documentos' => $documentos,
            ];
        }

        return $resultado;
    }

    public function normalizarPayload(array $payload): array
    {
        $tipoIncapacidad = strtoupper(trim((string) ($payload['tipo_incapacidad'] ?? 'INICIO')));

        return [
            'identificacion' => trim((string) ($payload['identificacion'] ?? $payload['documento_empleado'] ?? '')),
            'causa_id' => trim((string) ($payload['causa_id'] ?? '')),
            'diagnostico_id' => trim((string) ($payload['diagnostico_id'] ?? '')),
            'eps_id' => trim((string) ($payload['eps_id'] ?? '')),
            'arl_id' => trim((string) ($payload['arl_id'] ?? '')),
            'tipo_incapacidad' => $tipoIncapacidad,
            'fecha_inicio' => trim((string) ($payload['fecha_inicio'] ?? '')),
            'fecha_fin' => trim((string) ($payload['fecha_fin'] ?? '')),
            'observacion' => trim((string) ($payload['observacion'] ?? '')),
        ];
    }

    public function validarCatalogos(array $payload): ?string
    {
        $payload = $this->normalizarPayload($payload);

        if (! isset($this->obtenerCausas()[$payload['causa_id']])) {
            return 'La causa de incapacidad seleccionada no existe.';
        }

        if (! isset($this->obtenerDiagnosticos()[$payload['diagnostico_id']])) {
            return 'El diagnostico seleccionado no existe.';
        }

        if (! isset($this->obtenerEps()[$payload['eps_id']])) {
            return 'La EPS seleccionada no existe.';
        }

        if (! isset($this->obtenerArl()[$payload['arl_id']])) {
            return 'La ARL seleccionada no existe.';
        }

        return null;
    }

    public function calcularDiasIncapacidad(mixed $fechaInicio, mixed $fechaFin): ?int
    {
        try {
            $inicio = Carbon::parse($fechaInicio)->startOfDay();
            $fin = Carbon::parse($fechaFin)->startOfDay();

            return $inicio->diffInDays($fin) + 1;
        } catch (\Throwable) {
            return null;
        }
    }

    private function cambiarEstadoSolicitud(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        ?string $observacion,
        string $tipoEvento
    ): array {
        $detalle = $this->obtenerDetalleGestion($idNovedad);
        if (! $detalle) {
            return [
                'ok' => false,
                'message' => 'No se encontro la solicitud de incapacidad.',
            ];
        }

        $estadoActual = strtoupper(trim((string) ($detalle->estado ?? '')));
        $nuevoEstado = strtoupper(trim($nuevoEstado));

        if (! $this->esTransicionValida($estadoActual, $nuevoEstado)) {
            return [
                'ok' => false,
                'message' => 'La transicion de estado solicitada no es valida para incapacidades.',
            ];
        }

        $connection = DB::connection(self::CONNECTION);
        $connection->beginTransaction();

        try {
            $datosDetalle = [
                'estado' => $nuevoEstado,
                'fecha_modifica' => now(),
            ];
            $datosNovedad = [
                'estado' => $nuevoEstado,
                'fecha_modifica' => now(),
                'usuario_modifica' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
            ];

            $observacion = $this->normalizarTexto($observacion);
            if ($observacion !== null) {
                $datosDetalle['observacion'] = $observacion;
                $datosNovedad['observacion'] = $observacion;
            }

            $connection->table('EMP_INCAPACIDADES')
                ->where('id', (string) $detalle->id_origen)
                ->update($datosDetalle);

            $connection->table('EMP_NOVEDADES')
                ->where('id', $idNovedad)
                ->update($datosNovedad);

            $connection->commit();

            return [
                'ok' => true,
                'message' => 'Estado de incapacidad actualizado correctamente.',
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();

            return [
                'ok' => false,
                'message' => 'No fue posible actualizar el estado de la incapacidad.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function registrarHistorial(
        string $idNovedad,
        string $estadoAnterior,
        string $estadoNuevo,
        string $documentoActor,
        string $tipoEvento,
        ?string $observacion
    ): void {
        DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->insert([
                'id' => (string) Str::uuid(),
                'id_novedad' => $idNovedad,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'tipo_evento' => $tipoEvento,
                'fecha_cambio' => now(),
                'usuario_accion' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                'observacion' => $observacion,
            ]);
    }

    private function obtenerTipoIncapacidadId(): ?string
    {
        return Cache::remember('emp_novedades_tipo_incapacidad_id', self::CACHE_TTL_SECONDS, function () {
            $id = DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->where(function ($query) {
                    $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_INCAPACIDADES'])
                        ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['INCAPACIDAD']);
                })
                ->value('id');

            if (! is_string($id) || trim($id) === '') {
                return null;
            }

            return trim($id);
        });
    }

    private function nuevaConsultaBase(): Builder
    {
        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES as n')
            ->join('EMP_INCAPACIDADES as i', 'i.id', '=', 'n.id_origen')
            ->leftJoin('EMP_CAUSAS_INCAPACIDAD as c', 'c.id', '=', 'i.id_causa_incapacidad')
            ->leftJoin('EMP_DIAGNOSTICOS as d', 'd.id', '=', 'i.id_diagnostico')
            ->leftJoin('EMP_EPS as e', 'e.id', '=', 'i.id_eps')
            ->leftJoin('EMP_ARL as a', 'a.id', '=', 'i.id_arl')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->where(function ($query) {
                $query->whereRaw("UPPER(NVL(t.tabla, '')) = ?", ['EMP_INCAPACIDADES'])
                    ->orWhereRaw("UPPER(NVL(t.descripcion, '')) = ?", ['INCAPACIDAD']);
            })
            ->select([
                'n.id as id_novedad',
                'n.id_persona',
                'n.id_origen',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.fecha_creacion',
                'n.observacion as novedad_observacion',
                'i.documento_persona',
                'i.documento_radica',
                'i.id_causa_incapacidad',
                'i.id_diagnostico',
                'i.id_eps',
                'i.id_arl',
                'i.tipo_incapacidad',
                'i.observacion',
                'c.causa',
                DB::raw('d.CodigoCie as diagnostico_codigo'),
                DB::raw('d.DescCie as diagnostico_descripcion'),
                DB::raw('e.EPSNombre as eps_nombre'),
                DB::raw('e.EPSNit as eps_nit'),
                DB::raw('a.ARLNombre as arl_nombre'),
                DB::raw('a.ARLNit as arl_nit'),
                DB::raw('NVL(t.requiere_bloqueo, 0) as requiere_bloqueo_tipo'),
                DB::raw('NVL(t.id_bloqueo_logtrans, 0) as id_bloqueo_logtrans_tipo'),
                DB::raw('NVL(t.id_bloqueo_fics, 0) as id_bloqueo_fics_tipo'),
            ]);
    }

    private function esTransicionValida(string $estadoActual, string $nuevoEstado): bool
    {
        $estadoActual = strtoupper(trim($estadoActual));
        $nuevoEstado = strtoupper(trim($nuevoEstado));

        return match ($estadoActual) {
            self::ESTADO_RADICADO => in_array($nuevoEstado, [
                self::ESTADO_APROBADO,
                self::ESTADO_RECHAZADO,
                self::ESTADO_ANULADO,
            ], true),
            default => false,
        };
    }

    private function generarBloqueoSiAplica(object $detalle, BloqueoService $bloqueoService): string|bool
    {
        if ((int) ($detalle->requiere_bloqueo_tipo ?? 0) !== 1) {
            return false;
        }

        $idBloqueoLogtrans = (int) ($detalle->id_bloqueo_logtrans_tipo ?? 0);
        if ($idBloqueoLogtrans < 1) {
            $idBloqueoLogtrans = BloqueoService::ID_BLOQUEO_LOGTRANS_INCAPACIDAD;
        }

        $idBloqueoFics = (int) ($detalle->id_bloqueo_fics_tipo ?? 0);
        if ($idBloqueoFics < 1) {
            $idBloqueoFics = BloqueoService::ID_BLOQUEO_FICS_INCAPACIDAD;
        }

        $persona = $this->buscarPersona(trim((string) ($detalle->documento_persona ?? $detalle->id_persona ?? '')));
        if (! $persona || (int) ($persona['id'] ?? 0) < 1) {
            return false;
        }

        $incapacidadDatos = (object) [
            'IncFecIni' => $detalle->fecha_inicio,
            'IncFecFin' => $detalle->fecha_fin,
            'diagnostico' => (object) [
                'CodigoCie' => trim((string) ($detalle->diagnostico_codigo ?? '')),
            ],
        ];

        $resultadoLogtrans = $bloqueoService->bloqNovedadLogtransInc(
            (int) $persona['id'],
            $incapacidadDatos,
            $idBloqueoLogtrans
        );
        if ($resultadoLogtrans === 'error') {
            return 'error';
        }

        $resultadoFics = BloqueoService::crearBloqueoFICS(
            (string) ($persona['identificacion'] ?? ''),
            $idBloqueoFics,
            $detalle->fecha_inicio,
            $detalle->fecha_fin
        );

        return $resultadoLogtrans === 'bloqueado' || $resultadoFics === true
            ? 'bloqueado'
            : false;
    }

    private function normalizarTexto(mixed $value): ?string
    {
        $texto = trim((string) $value);

        return $texto === '' ? null : $texto;
    }

    private function normalizarDocumentoActor(string $documentoActor, string $documentoPersona): string
    {
        $documentoActor = trim($documentoActor);
        $documentoPersona = trim($documentoPersona);

        if ($documentoActor === '') {
            return $documentoPersona;
        }

        $actorUpper = strtoupper($documentoActor);
        if (in_array($actorUpper, ['SISTEMA', 'SYSTEM', 'AUTOGESTION'], true)) {
            return $documentoPersona;
        }

        return $documentoActor;
    }

    private function formatearDiagnosticoLabel(object $item): string
    {
        $codigo = trim((string) ($item->codigo_cie ?? $item->CodigoCie ?? ''));
        $descripcion = trim((string) ($item->desc_cie ?? $item->DescCie ?? ''));
        $label = $codigo;

        if ($descripcion !== '') {
            $label .= ' - '.$descripcion;
        }

        return $label;
    }

    private function limpiarAdjuntosFallidos(?array $resultadoAdjuntos): void
    {
        if (! is_array($resultadoAdjuntos) || ! ($resultadoAdjuntos['ok'] ?? false)) {
            return;
        }

        $rutas = collect((array) ($resultadoAdjuntos['adjuntos'] ?? []))
            ->map(fn ($adjunto) => is_array($adjunto) ? ($adjunto['ruta_documento'] ?? null) : null)
            ->filter(fn ($ruta) => is_string($ruta) && trim($ruta) !== '')
            ->values()
            ->all();

        if ($rutas === []) {
            return;
        }

        $this->documentalStorage->eliminarMultiplesSiExisten($rutas);
    }
}

