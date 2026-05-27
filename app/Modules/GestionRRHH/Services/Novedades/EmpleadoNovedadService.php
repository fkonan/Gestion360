<?php

namespace App\Modules\GestionRRHH\Services\Novedades;

use App\Models\User;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\GestionRRHH\Services\JefeEquipoService;
use App\Modules\GestionRRHH\Services\Novedades\NovedadTipoResolver;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmpleadoNovedadService
{
    private const CONNECTION = 'oracle-360';
    private const TIPO_CACHE_KEY = 'gestion_rrhh_novedades_tipos_indexados';
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly EmpleadoPermisoService $permisoService,
        private readonly EmpleadoPermisoDocumentoService $documentoService,
        private readonly JefeEquipoService $jefeEquipoService
    ) {}

    public function obtenerMisNovedadesPaginadas(string $documentoPersona, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $documentoPersona = trim($documentoPersona);
        $query = $this->nuevaConsultaBase();

        if (($filtros['incluir_radicadas_por'] ?? false) === true) {
            $query->where(function ($subquery) use ($documentoPersona) {
                $subquery
                    ->where('n.id_persona', $documentoPersona)
                    ->orWhereExists(function ($permisoQuery) use ($documentoPersona) {
                        $permisoQuery->select(DB::raw(1))
                            ->from('EMP_PERMISOS as p')
                            ->whereColumn('p.id', 'n.id_origen')
                            ->where('p.documento_radica', $documentoPersona);
                    })
                    ->orWhereExists(function ($incapacidadQuery) use ($documentoPersona) {
                        $incapacidadQuery->select(DB::raw(1))
                            ->from('EMP_INCAPACIDADES as i')
                            ->whereColumn('i.id', 'n.id_origen')
                            ->where('i.documento_radica', $documentoPersona);
                    })
                    ->orWhereExists(function ($vacacionQuery) use ($documentoPersona) {
                        $vacacionQuery->select(DB::raw(1))
                            ->from('EMP_VACACIONES as v')
                            ->whereColumn('v.id', 'n.id_origen')
                            ->where('v.documento_radica', $documentoPersona);
                    })
                    ->orWhereExists(function ($historialQuery) use ($documentoPersona) {
                        $historialQuery->select(DB::raw(1))
                            ->from('EMP_NOVEDADES_HISTORIAL as h')
                            ->whereColumn('h.id_novedad', 'n.id')
                            ->where('h.estado_nuevo', EmpleadoPermisoService::ESTADO_RADICADO)
                            ->where('h.usuario_accion', $documentoPersona);
                    });

                if ($this->tablaPermisosPermanentesDisponible()) {
                    $subquery->orWhereExists(function ($permisoPermanenteQuery) use ($documentoPersona) {
                        $permisoPermanenteQuery->select(DB::raw(1))
                            ->from('EMP_PERMISOS_PERMANENTES as pp')
                            ->whereColumn('pp.id', 'n.id_origen')
                            ->where('pp.documento_radica', $documentoPersona);
                    });
                }
            });
        } else {
            $query->where('n.id_persona', $documentoPersona);
        }

        $this->aplicarFiltros($query, $filtros);

        return $this->enriquecerPaginador($query->orderByDesc('n.fecha_creacion')->paginate($perPage)->withQueryString());
    }

    public function obtenerMisSolicitudesPaginadas(array $identificadoresActor, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $identificadores = collect($identificadoresActor)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query = $this->nuevaConsultaBase();

        if ($identificadores === []) {
            $query->whereRaw('1 = 0');
        } else {
            $this->aplicarWhereInSegmentado($query, 'n.id_persona', $identificadores);
        }

        $this->aplicarFiltros($query, $filtros);

        return $this->enriquecerPaginador($query->orderByDesc('n.fecha_creacion')->paginate($perPage)->withQueryString());
    }

    public function obtenerNovedadesEquipoPaginadas(string $documentoJefe, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $documentoActor = trim($documentoJefe);
        $equipoJefe = collect($this->jefeEquipoService->obtenerEmpleadosDirectos($documentoActor));
        $equipoAsociado = collect(EmpleadoService::obtenerConductoresActivosDeAsociado($documentoActor));

        $documentosEquipo = $equipoJefe
            ->merge($equipoAsociado)
            ->pluck('doc_empleado')
            ->map(fn ($doc) => trim((string) $doc))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $query = $this->nuevaConsultaBase();

        if ($documentosEquipo === []) {
            $query->whereRaw('1 = 0');
        } else {
            $this->aplicarWhereInSegmentado($query, 'n.id_persona', $documentosEquipo);
        }

        $this->aplicarFiltros($query, $filtros);

        return $this->enriquecerPaginador($query->orderByDesc('n.fecha_creacion')->paginate($perPage)->withQueryString());
    }

    public function obtenerNovedadesPaginadas(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->nuevaConsultaBase();
        $this->aplicarFiltros($query, $filtros);

        return $this->enriquecerPaginador($query->orderByDesc('n.fecha_creacion')->paginate($perPage)->withQueryString());
    }

    public function obtenerNovedadesPersonaPaginadas(string $documentoPersona, array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $documentoPersona = trim($documentoPersona);
        $query = $this->nuevaConsultaBase();

        if ($documentoPersona === '') {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('n.id_persona', $documentoPersona);
        }

        $this->aplicarFiltros($query, $filtros);

        return $this->enriquecerPaginador($query->orderByDesc('n.fecha_creacion')->paginate($perPage)->withQueryString());
    }

    public function obtenerTiposRadicacionDisponibles(): array
    {
        if (! Schema::connection(self::CONNECTION)->hasTable('EMP_NOVEDADES_TIPO')) {
            return [];
        }

        $schema = Schema::connection(self::CONNECTION);
        $columnas = ['id', 'descripcion', 'tabla'];
        $columnaActivo = null;

        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'codigo')) {
            $columnas[] = 'codigo';
        }
        if ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'ACTIVO')) {
            $columnaActivo = 'ACTIVO';
        } elseif ($schema->hasColumn('EMP_NOVEDADES_TIPO', 'activo')) {
            $columnaActivo = 'activo';
        }

        $mapaTipos = [
            NovedadTipoResolver::TIPO_PERMISO => [
                'ruta' => 'gestionRRHH.permisos.create',
                'icono' => 'fa-file-signature',
            ],
            NovedadTipoResolver::TIPO_INCAPACIDAD => [
                'ruta' => 'gestionRRHH.permisos.incapacidades.create',
                'icono' => 'fa-notes-medical',
            ],
            NovedadTipoResolver::TIPO_VACACION => [
                'ruta' => 'gestionRRHH.permisos.vacaciones.create',
                'icono' => 'fa-umbrella-beach',
            ],
            NovedadTipoResolver::TIPO_PERMISO_PERMANENTE => [
                'ruta' => 'gestionRRHH.permisos.permisos-permanentes.create',
                'icono' => 'fa-calendar-week',
            ],
        ];

        $query = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_TIPO')
            ->select($columnas)
            ->orderBy('descripcion');

        if ($columnaActivo !== null) {
            $query->addSelect(DB::raw($columnaActivo.' as activo_flag'));
        }

        return $query->get()
            ->map(function ($item) use ($mapaTipos) {
                $codigo = $this->normalizarTipoCodigo(
                    (string) ($item->tabla ?? $item->codigo ?? ''),
                    (string) ($item->descripcion ?? '')
                );

                $estaActivo = $this->valorActivo($item->activo_flag ?? null);

                $configTipo = $mapaTipos[$codigo] ?? null;
                if (! $configTipo) {
                    return null;
                }

                return [
                    'id' => trim((string) ($item->id ?? '')),
                    'codigo' => $codigo,
                    'label' => trim((string) ($item->descripcion ?? $codigo)),
                    'icono' => (string) $configTipo['icono'],
                    'ruta' => (string) $configTipo['ruta'],
                    'activo' => $estaActivo,
                ];
            })
            ->filter(fn (?array $item) => is_array($item) && ($item['activo'] ?? false) === true)
            ->values()
            ->all();
    }

    public function usuarioPuedeConsultarNovedad(?User $user, string $idNovedad): bool
    {
        if (! $user) {
            return false;
        }

        $novedad = $this->obtenerNovedad($idNovedad);
        if (! $novedad) {
            return false;
        }

        if ($this->permisoService->esUsuarioSuperAdmin($user) || $this->permisoService->esUsuarioRrhh($user)) {
            return true;
        }

        $documentoActor = $this->permisoService->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        $documentoPersona = trim((string) ($novedad->id_persona ?? ''));
        if ($documentoPersona !== '' && $documentoPersona === $documentoActor) {
            return true;
        }

        $radicadores = $this->permisoService->obtenerRadicadoresPorNovedades([$idNovedad]);
        $documentoRadica = trim((string) data_get($radicadores, $idNovedad.'.documento', ''));
        if ($documentoRadica !== '' && $documentoRadica === $documentoActor) {
            return true;
        }

        return $documentoPersona !== ''
            && $this->jefeEquipoService->esJefeDirectoDeEmpleado($documentoActor, $documentoPersona);
    }

    public function obtenerNovedad(string $idNovedad): ?object
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return null;
        }

        return $this->nuevaConsultaBase()
            ->where('n.id', $idNovedad)
            ->select([
                'n.id as id_novedad',
                'n.id_persona',
                'n.id_tipo_novedad',
                'n.id_origen',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.fecha_creacion',
                'n.observacion',
                't.descripcion as tipo_descripcion',
                't.tabla as tipo_tabla_db',
            ])
            ->first();
    }

    public function obtenerRutaGestionPorNovedad(object $novedad): ?string
    {
        $tipoCodigo = strtoupper(trim((string) ($novedad->tipo_codigo ?? '')));
        $idNovedad = trim((string) ($novedad->id_novedad ?? ''));

        if ($tipoCodigo === 'INCAPACIDAD' && $idNovedad !== '') {
            return route('gestionRRHH.permisos.incapacidades.gestion', ['idNovedad' => $idNovedad]);
        }

        return null;
    }

    public function obtenerTipoCodigoPorId(?string $idTipo): ?string
    {
        if (! is_string($idTipo) || trim($idTipo) === '') {
            return null;
        }

        foreach ($this->obtenerTiposIndexados() as $tipo) {
            if (($tipo['id'] ?? null) === trim($idTipo)) {
                return $tipo['codigo'];
            }
        }

        return null;
    }

    private function nuevaConsultaBase(): Builder
    {
        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES as n')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->select([
                'n.id as id_novedad',
                'n.id_persona',
                'n.id_tipo_novedad',
                'n.id_origen',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.fecha_creacion',
                'n.observacion',
                't.descripcion as tipo_descripcion',
                't.tabla as tipo_tabla_db',
            ]);
    }

    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $persona = trim((string) ($filtros['persona'] ?? ''));
        if ($persona !== '') {
            $personaUpper = mb_strtoupper($persona, 'UTF-8');

            $query->where(function ($subquery) use ($persona, $personaUpper) {
                $subquery->where('n.id_persona', 'like', '%'.$persona.'%')
                    ->orWhereExists(function ($personaQuery) use ($personaUpper) {
                        $personaQuery->select(DB::raw(1))
                            ->from('PRS_PERSONAS as p')
                            ->whereColumn('p.numero_documento', 'n.id_persona')
                            ->whereRaw(
                                "UPPER(TRIM(NVL(p.nombres, '') || ' ' || NVL(p.primer_apellido, '') || ' ' || NVL(p.segundo_apellido, ''))) LIKE ?",
                                ['%'.$personaUpper.'%']
                            );
                    });
            });
        }

        $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
        if ($estado !== '') {
            $query->where('n.estado', $estado);
        }

        $tipo = strtoupper(trim((string) ($filtros['tipo'] ?? '')));
        if ($tipo !== '') {
            $tablaEsperada = $this->tablaPorTipo($tipo);
            $query->where(function ($subquery) use ($tipo, $tablaEsperada) {
                if ($tablaEsperada !== null) {
                    $subquery->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', [$tablaEsperada]);
                    $subquery->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', [$tipo]);
                    return;
                }

                $subquery->whereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', [$tipo]);
            });
        }

        if (($filtros['solo_aprobadas'] ?? false) === true) {
            $query->where('n.estado', EmpleadoPermisoService::ESTADO_APROBADO);
        }

        if (($filtros['solo_no_aprobadas'] ?? false) === true) {
            $query->where('n.estado', '<>', EmpleadoPermisoService::ESTADO_APROBADO);
        }

        if (($filtros['solo_gestionables_rrhh'] ?? false) === true) {
            $query->where(function ($subquery) {
                $subquery
                    ->where(function ($permisoQuery) {
                        $permisoQuery
                            ->where(function ($tipoPermisoQuery) {
                                $tipoPermisoQuery
                                    ->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', ['EMP_PERMISOS'])
                                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['PERMISO']);
                            })
                            ->where('n.estado', EmpleadoPermisoService::ESTADO_JEFE_APROBADO);
                    })
                    ->orWhere(function ($incapacidadQuery) {
                        $incapacidadQuery
                            ->where(function ($tipoIncapacidadQuery) {
                                $tipoIncapacidadQuery
                                    ->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', ['EMP_INCAPACIDADES'])
                                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['INCAPACIDAD']);
                            })
                            ->where('n.estado', 'RADICADO');
                    })
                    ->orWhere(function ($vacacionQuery) {
                        $vacacionQuery
                            ->where(function ($tipoVacacionQuery) {
                                $tipoVacacionQuery
                                    ->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', ['EMP_VACACIONES'])
                                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['VACACION']);
                            })
                            ->where('n.estado', EmpleadoPermisoService::ESTADO_JEFE_APROBADO);
                    })
                    ->orWhere(function ($permisoPermanenteQuery) {
                        $permisoPermanenteQuery
                            ->where(function ($tipoPermisoPermanenteQuery) {
                                $tipoPermisoPermanenteQuery
                                    ->whereRaw('UPPER(NVL(t.tabla, \'\')) = ?', ['EMP_PERMISOS_PERMANENTES'])
                                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['PERMISO_PERMANENTE'])
                                    ->orWhereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['PERMISO PERMANENTE']);
                            })
                            ->where('n.estado', EmpleadoPermisoService::ESTADO_JEFE_APROBADO);
                    });
            });
        }

        if (($filtros['solo_hoy'] ?? false) === true) {
            $query->whereRaw('TRUNC(n.fecha_inicio) = TRUNC(SYSDATE)');
        }

        if (($filtros['solo_activas_hoy'] ?? false) === true) {
            $query->whereRaw('TRUNC(SYSDATE) BETWEEN TRUNC(n.fecha_inicio) AND TRUNC(NVL(n.fecha_fin, n.fecha_inicio))');
        }
    }

    private function enriquecerPaginador(LengthAwarePaginator $paginador): LengthAwarePaginator
    {
        $items = $paginador->getCollection();
        if ($items->isEmpty()) {
            return $paginador;
        }

        $tipos = $this->obtenerTiposIndexados();
        $tipoPermisoId = $this->resolverTipoId('PERMISO', $tipos);
        $tipoPermisoPermanenteId = $this->resolverTipoId('PERMISO_PERMANENTE', $tipos);
        $tipoIncapacidadId = $this->resolverTipoId('INCAPACIDAD', $tipos);
        $tipoVacacionId = $this->resolverTipoId('VACACION', $tipos);

        $idsNovedad = $items->pluck('id_novedad')->map(fn ($id) => trim((string) $id))->filter()->values()->all();
        $idsOrigenPermiso = $items
            ->filter(fn ($item) => trim((string) ($item->id_tipo_novedad ?? '')) === $tipoPermisoId)
            ->pluck('id_origen')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();
        $idsOrigenPermisoPermanente = $items
            ->filter(fn ($item) => trim((string) ($item->id_tipo_novedad ?? '')) === $tipoPermisoPermanenteId)
            ->pluck('id_origen')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();
        $idsOrigenIncapacidad = $items
            ->filter(fn ($item) => trim((string) ($item->id_tipo_novedad ?? '')) === $tipoIncapacidadId)
            ->pluck('id_origen')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();
        $idsOrigenVacacion = $items
            ->filter(fn ($item) => trim((string) ($item->id_tipo_novedad ?? '')) === $tipoVacacionId)
            ->pluck('id_origen')
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values()
            ->all();

        $detallesPermisos = $this->obtenerDetallesPermisos($idsOrigenPermiso);
        $detallesPermisosPermanentes = $this->obtenerDetallesPermisosPermanentes($idsOrigenPermisoPermanente);
        $detallesIncapacidades = $this->obtenerDetallesIncapacidades($idsOrigenIncapacidad);
        $detallesVacaciones = $this->obtenerDetallesVacaciones($idsOrigenVacacion);
        $radicadores = $this->permisoService->obtenerRadicadoresPorNovedades($idsNovedad);
        $conteosAdjuntos = $this->documentoService->obtenerConteoAdjuntosPorNovedades($idsNovedad);
        $personas = $this->obtenerPersonasPorDocumentos(
            $items->pluck('id_persona')
                ->map(fn ($doc) => trim((string) $doc))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );
        $nombresRadicadoresPorDocumento = [];

        $items->transform(function ($item) use (
            $tipoPermisoId,
            $tipoPermisoPermanenteId,
            $tipoIncapacidadId,
            $tipoVacacionId,
            $detallesPermisos,
            $detallesPermisosPermanentes,
            $detallesIncapacidades,
            $detallesVacaciones,
            $radicadores,
            $conteosAdjuntos,
            $personas,
            &$nombresRadicadoresPorDocumento
        ) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            $idOrigen = trim((string) ($item->id_origen ?? ''));
            $tipoNovedadId = trim((string) ($item->id_tipo_novedad ?? ''));
            $tipoCodigo = $this->normalizarTipoCodigo((string) ($item->tipo_tabla_db ?? ''), (string) ($item->tipo_descripcion ?? ''));
            $radicador = $radicadores[$idNovedad] ?? [];
            $persona = $personas[trim((string) ($item->id_persona ?? ''))] ?? null;

            $item->tipo_codigo = $tipoCodigo;
            $item->tipo_label = trim((string) ($item->tipo_descripcion ?? '')) !== ''
                ? trim((string) ($item->tipo_descripcion ?? ''))
                : ucfirst(mb_strtolower($tipoCodigo, 'UTF-8'));
            $item->persona_nombre = $persona['nombre'] ?? 'Sin nombre';
            $item->documento_persona = trim((string) ($item->id_persona ?? ''));
            $item->radicado_por_documento = $radicador['documento'] ?? null;
            $item->radicado_por_nombre = $radicador['nombre'] ?? null;
            $item->adjuntos_count = (int) ($conteosAdjuntos[$idNovedad] ?? 0);
            $item->tiene_adjuntos = $item->adjuntos_count > 0;
            $item->detalle_descripcion = null;
            $item->origen_gestion_url = $this->obtenerRutaGestionPorNovedad((object) [
                'id_novedad' => $idNovedad,
                'tipo_codigo' => $tipoCodigo,
            ]);

            if ($tipoNovedadId === $tipoPermisoId && isset($detallesPermisos[$idOrigen])) {
                $detalle = $detallesPermisos[$idOrigen];
                $item->documento_persona = trim((string) ($detalle->documento_persona ?? $item->documento_persona));
                $item->documento_radica = trim((string) ($detalle->documento_radica ?? ($item->radicado_por_documento ?? '')));
                $item->motivo = trim((string) ($detalle->motivo ?? ''));
                $item->otro_motivo = trim((string) ($detalle->otro_motivo ?? ''));
                $item->actividad = trim((string) ($detalle->actividad ?? ''));
                $item->detalle_descripcion = $item->actividad !== '' ? $item->actividad : null;
            } elseif ($tipoNovedadId === $tipoPermisoPermanenteId && isset($detallesPermisosPermanentes[$idOrigen])) {
                $detalle = $detallesPermisosPermanentes[$idOrigen];
                $item->documento_persona = trim((string) ($detalle->documento_persona ?? $item->documento_persona));
                $item->documento_radica = trim((string) ($detalle->documento_radica ?? ($item->radicado_por_documento ?? '')));
                if (
                    $item->documento_radica !== ''
                    && (
                        trim((string) ($item->radicado_por_documento ?? '')) === ''
                        || $this->esDocumentoSistema((string) ($item->radicado_por_documento ?? ''))
                    )
                ) {
                    $item->radicado_por_documento = $item->documento_radica;
                    if (empty($nombresRadicadoresPorDocumento[$item->documento_radica])) {
                        $personaRadica = $this->permisoService->buscarPersona($item->documento_radica);
                        $nombresRadicadoresPorDocumento[$item->documento_radica] = trim((string) ($personaRadica['nombre'] ?? ''));
                    }

                    $nombreRadica = trim((string) ($nombresRadicadoresPorDocumento[$item->documento_radica] ?? ''));
                    if ($nombreRadica !== '') {
                        $item->radicado_por_nombre = $nombreRadica;
                    }
                }
                $item->jornada = trim((string) ($detalle->jornada ?? ''));
                $item->horario_fijo = (int) ($detalle->horario_fijo ?? 0);
                $item->hora_salida_j1 = trim((string) ($detalle->hora_salida_j1 ?? ''));
                $item->hora_ingreso_j1 = trim((string) ($detalle->hora_ingreso_j1 ?? ''));
                $item->hora_salida_j2 = trim((string) ($detalle->hora_salida_j2 ?? ''));
                $item->hora_ingreso_j2 = trim((string) ($detalle->hora_ingreso_j2 ?? ''));
                $item->detalle_descripcion = collect([
                    $item->jornada !== '' ? 'Jornada: '.$this->formatearJornadaPermisoPermanente($item->jornada) : null,
                    $item->horario_fijo === 1 ? 'Horario fijo: SI' : 'Horario fijo: NO',
                ])->filter()->implode(' | ');
            } elseif ($tipoNovedadId === $tipoIncapacidadId && isset($detallesIncapacidades[$idOrigen])) {
                $detalle = $detallesIncapacidades[$idOrigen];
                $item->documento_persona = trim((string) ($detalle->documento_persona ?? $item->documento_persona));
                $item->documento_radica = trim((string) ($detalle->documento_radica ?? ($item->radicado_por_documento ?? '')));
                if (
                    $item->documento_radica !== ''
                    && (
                        trim((string) ($item->radicado_por_documento ?? '')) === ''
                        || $this->esDocumentoSistema((string) ($item->radicado_por_documento ?? ''))
                    )
                ) {
                    $item->radicado_por_documento = $item->documento_radica;
                    if (empty($nombresRadicadoresPorDocumento[$item->documento_radica])) {
                        $personaRadica = $this->permisoService->buscarPersona($item->documento_radica);
                        $nombresRadicadoresPorDocumento[$item->documento_radica] = trim((string) ($personaRadica['nombre'] ?? ''));
                    }

                    $nombreRadica = trim((string) ($nombresRadicadoresPorDocumento[$item->documento_radica] ?? ''));
                    if ($nombreRadica !== '') {
                        $item->radicado_por_nombre = $nombreRadica;
                    }
                }
                $item->tipo_incapacidad = trim((string) ($detalle->tipo_incapacidad ?? ''));
                $item->causa = trim((string) ($detalle->causa ?? ''));
                $item->diagnostico_codigo = trim((string) ($detalle->diagnostico_codigo ?? ''));
                $item->diagnostico_descripcion = trim((string) ($detalle->diagnostico_descripcion ?? ''));
                $item->eps_nombre = trim((string) ($detalle->eps_nombre ?? ''));
                $item->arl_nombre = trim((string) ($detalle->arl_nombre ?? ''));
                $item->detalle_descripcion = collect([
                    $item->causa !== '' ? $item->causa : null,
                    $item->diagnostico_codigo !== '' ? $item->diagnostico_codigo : null,
                    $item->diagnostico_descripcion !== '' ? $item->diagnostico_descripcion : null,
                ])->filter()->implode(' - ');
            } elseif ($tipoNovedadId === $tipoVacacionId && isset($detallesVacaciones[$idOrigen])) {
                $detalle = $detallesVacaciones[$idOrigen];
                $item->documento_persona = trim((string) ($detalle->documento_persona ?? $item->documento_persona));
                $item->documento_radica = trim((string) ($detalle->documento_radica ?? ($item->radicado_por_documento ?? '')));
                $item->tipo_aprobador = strtoupper(trim((string) ($detalle->tipo_aprobador ?? '')));
                $item->documento_aprobador = trim((string) ($detalle->documento_aprobador ?? ''));
                if (
                    $item->documento_radica !== ''
                    && (
                        trim((string) ($item->radicado_por_documento ?? '')) === ''
                        || $this->esDocumentoSistema((string) ($item->radicado_por_documento ?? ''))
                    )
                ) {
                    $item->radicado_por_documento = $item->documento_radica;
                    if (empty($nombresRadicadoresPorDocumento[$item->documento_radica])) {
                        $personaRadica = $this->permisoService->buscarPersona($item->documento_radica);
                        $nombresRadicadoresPorDocumento[$item->documento_radica] = trim((string) ($personaRadica['nombre'] ?? ''));
                    }

                    $nombreRadica = trim((string) ($nombresRadicadoresPorDocumento[$item->documento_radica] ?? ''));
                    if ($nombreRadica !== '') {
                        $item->radicado_por_nombre = $nombreRadica;
                    }
                }
                $item->detalle_descripcion = collect([
                    trim((string) ($detalle->fecha_inicio ?? '')) !== '' ? 'Inicio: '.trim((string) $detalle->fecha_inicio) : null,
                    trim((string) ($detalle->fecha_fin ?? '')) !== '' ? 'Fin: '.trim((string) $detalle->fecha_fin) : null,
                ])->filter()->implode(' | ');
            } else {
                $item->documento_radica = trim((string) ($item->radicado_por_documento ?? ''));
            }

            $item->fecha_inicio = $item->fecha_inicio ? Carbon::parse($item->fecha_inicio) : null;
            $item->fecha_fin = $item->fecha_fin ? Carbon::parse($item->fecha_fin) : null;

            return $item;
        });

        $paginador->setCollection($items);

        return $paginador;
    }

    private function obtenerDetallesPermisos(array $idsOrigen): array
    {
        if ($idsOrigen === []) {
            return [];
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_PERMISOS')
            ->whereIn('id', $idsOrigen)
            ->get()
            ->keyBy(fn ($item) => trim((string) ($item->id ?? '')))
            ->all();
    }

    private function formatearJornadaPermisoPermanente(?string $valor): string
    {
        $jornada = strtoupper(trim((string) $valor));

        return match ($jornada) {
            '1', 'MANANA', 'MAÑANA' => 'Manana (Jornada 1)',
            '2', 'TARDE' => 'Tarde (Jornada 2)',
            '1,2', 'AMBAS' => 'Ambas jornadas',
            default => trim((string) $valor),
        };
    }

    private function obtenerDetallesPermisosPermanentes(array $idsOrigen): array
    {
        if ($idsOrigen === []) {
            return [];
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_PERMISOS_PERMANENTES')
            ->whereIn('id', $idsOrigen)
            ->get()
            ->keyBy(fn ($item) => trim((string) ($item->id ?? '')))
            ->all();
    }

    private function obtenerDetallesIncapacidades(array $idsOrigen): array
    {
        if ($idsOrigen === []) {
            return [];
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_INCAPACIDADES as i')
            ->leftJoin('EMP_CAUSAS_INCAPACIDAD as c', 'c.id', '=', 'i.id_causa_incapacidad')
            ->leftJoin('EMP_DIAGNOSTICOS as d', 'd.id', '=', 'i.id_diagnostico')
            ->leftJoin('EMP_EPS as e', 'e.id', '=', 'i.id_eps')
            ->leftJoin('EMP_ARL as a', 'a.id', '=', 'i.id_arl')
            ->whereIn('i.id', $idsOrigen)
            ->select([
                'i.*',
                'c.causa',
                DB::raw('d.CodigoCie as diagnostico_codigo'),
                DB::raw('d.DescCie as diagnostico_descripcion'),
                DB::raw('e.EPSNombre as eps_nombre'),
                DB::raw('a.ARLNombre as arl_nombre'),
            ])
            ->get()
            ->keyBy(fn ($item) => trim((string) ($item->id ?? '')))
            ->all();
    }

    private function obtenerDetallesVacaciones(array $idsOrigen): array
    {
        if ($idsOrigen === []) {
            return [];
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_VACACIONES')
            ->whereIn('id', $idsOrigen)
            ->get()
            ->keyBy(fn ($item) => trim((string) ($item->id ?? '')))
            ->all();
    }

    private function obtenerPersonasPorDocumentos(array $documentos): array
    {
        $resultado = [];
        foreach ($documentos as $documento) {
            $persona = $this->permisoService->buscarPersona((string) $documento);
            if ($persona) {
                $resultado[(string) $documento] = $persona;
            }
        }

        return $resultado;
    }

    private function obtenerTiposIndexados(): array
    {
        return Cache::remember(self::TIPO_CACHE_KEY, self::CACHE_TTL, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->select(['id', 'descripcion', 'tabla'])
                ->get()
                ->map(function ($item) {
                    $descripcion = trim((string) ($item->descripcion ?? ''));
                    $codigo = $this->normalizarTipoCodigo((string) ($item->tabla ?? ''), $descripcion);

                    return [
                        'id' => trim((string) ($item->id ?? '')),
                        'descripcion' => $descripcion,
                        'codigo' => $codigo,
                    ];
                })
                ->filter(fn ($item) => ($item['id'] ?? '') !== '' && ($item['codigo'] ?? '') !== '')
                ->values()
                ->all();
        });
    }

    private function resolverTipoId(string $codigo, array $tipos): ?string
    {
        $codigo = strtoupper(trim($codigo));
        foreach ($tipos as $tipo) {
            if (($tipo['codigo'] ?? null) === $codigo) {
                return $tipo['id'];
            }
        }

        return null;
    }

    private function normalizarTipoCodigo(string $codigo, string $descripcion): string
    {
        return NovedadTipoResolver::normalizar($codigo, $descripcion);
    }

    private function tablaPorTipo(string $tipo): ?string
    {
        return NovedadTipoResolver::tablaPorTipo($tipo);
    }

    private function esDocumentoSistema(string $documento): bool
    {
        $documento = strtoupper(trim($documento));

        return in_array($documento, ['SISTEMA', 'SYSTEM', 'AUTOGESTION'], true);
    }

    private function aplicarWhereInSegmentado(Builder $query, string $columna, array $valores): void
    {
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

    private function tablaPermisosPermanentesDisponible(): bool
    {
        return Cache::remember('gestion_rrhh_tabla_emp_permisos_permanentes', 600, function () {
            return Schema::connection(self::CONNECTION)->hasTable('EMP_PERMISOS_PERMANENTES');
        });
    }

    private function valorActivo(mixed $valor): bool
    {
        if ($valor === null) {
            return true;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        if (is_numeric($valor)) {
            return (int) $valor === 1;
        }

        $texto = strtoupper(trim((string) $valor));
        if ($texto === '') {
            return true;
        }

        return in_array($texto, ['1', 'ACTIVO', 'A', 'SI', 'S'], true);
    }
}
