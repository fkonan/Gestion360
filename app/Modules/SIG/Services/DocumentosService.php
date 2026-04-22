<?php

namespace App\Modules\SIG\Services;

use App\Modules\SIG\Models\Documentos;
use App\Modules\SIG\Models\DocumentosVersiones;
use App\Modules\SIG\Models\Procesos;
use App\Modules\SIG\Models\TiposDocumentos;
use App\Modules\SIG\Models\Ubicaciones;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentosService
{
    public function obtenerCategorias(): array
    {
        return [
            'gerenciales' => ['codigo' => 'G', 'titulo' => 'Procesos Gerenciales'],
            'misionales' => ['codigo' => 'M', 'titulo' => 'Procesos Misionales'],
            'apoyo' => ['codigo' => 'A', 'titulo' => 'Procesos de Apoyo'],
            'otros' => ['codigo' => 'O', 'titulo' => 'Otros Procesos'],
        ];
    }

    public function normalizarEstadoFiltro(string $estado): string
    {
        return in_array($estado, ['activos', 'inactivos', 'todos'], true) ? $estado : 'activos';
    }

    public function obtenerProcesosFiltro(string $codigoCategoria): Collection
    {
        $procesosCategoria = Procesos::where('categoria', $codigoCategoria)->get(['id', 'nombre']);

        return $procesosCategoria->map(function ($proceso) {
            return [
                'id' => $proceso->id,
                'label' => $proceso->nombre ?? $proceso->id,
            ];
        })->filter(fn ($p) => ! empty($p['label']))->unique('id')->sortBy('label')->values();
    }

    public function obtenerTiposFiltro(): Collection
    {
        $tiposCategoria = TiposDocumentos::orderBy('nombre')->get(['id', 'nombre']);

        return $tiposCategoria->map(function ($tipo) {
            return [
                'id' => $tipo->id,
                'label' => $tipo->nombre ?? $tipo->id,
            ];
        })->filter(fn ($t) => ! empty($t['label']))->unique('id')->sortBy('label')->values();
    }

    public function obtenerResumenProcesosMapa(bool $puedeVerTodo, ?string $centroCostoCodigo): array
    {
        $procesos = Procesos::whereIn('categoria', ['G', 'M', 'A'])
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'categoria']);

        if ($procesos->isEmpty()) {
            return [];
        }

        $documentosQuery = Documentos::query()
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->whereIn('id_proceso', $procesos->pluck('id'));

        $this->aplicarFiltroVisibilidadDocumentos($documentosQuery, $puedeVerTodo, $centroCostoCodigo);

        $documentos = $documentosQuery->get(['id', 'id_proceso', 'id_tipo_doc', 'estado']);
        $documentosPorProceso = $documentos->groupBy('id_proceso');
        $documentoIds = $documentos->pluck('id')->all();
        $tiposDocumento = $documentos->pluck('id_tipo_doc')->filter()->unique()->all();

        $ultimosEstados = $documentoIds
            ? DocumentosVersiones::whereIn('documento_id', $documentoIds)
                ->orderByDesc('id')
                ->get(['documento_id', 'estado'])
                ->unique('documento_id')
                ->keyBy('documento_id')
            : collect();

        $tiposDocumentoPorId = empty($tiposDocumento)
            ? collect()
            : TiposDocumentos::whereIn('id', $tiposDocumento)
                ->get(['id', 'nombre'])
                ->keyBy('id');

        $titulosCategoria = collect($this->obtenerCategorias())
            ->mapWithKeys(fn ($categoria) => [$categoria['codigo'] => $categoria['titulo']]);

        return $procesos->mapWithKeys(function ($proceso) use ($documentosPorProceso, $ultimosEstados, $tiposDocumentoPorId, $titulosCategoria) {
            $documentosProceso = $documentosPorProceso->get($proceso->id, collect());
            $tiposResumen = $documentosProceso
                ->where('estado', 'ACTIVO')
                ->groupBy(fn ($documento) => (string) ($documento->id_tipo_doc ?? ''))
                ->map(function ($items, $tipoId) use ($tiposDocumentoPorId) {
                    $tipoId = trim((string) $tipoId);
                    $nombreTipo = $tipoId !== ''
                        ? ($tiposDocumentoPorId->get($tipoId)?->nombre ?? 'Tipo sin nombre')
                        : 'Sin tipo asignado';

                    return [
                        'nombre' => $nombreTipo,
                        'cantidad' => $items->count(),
                    ];
                })
                ->sort(function (array $a, array $b) {
                    if ($a['cantidad'] === $b['cantidad']) {
                        return strnatcasecmp($a['nombre'], $b['nombre']);
                    }

                    return $b['cantidad'] <=> $a['cantidad'];
                })
                ->values()
                ->all();

            return [
                (string) $proceso->id => [
                    'id' => (int) $proceso->id,
                    'nombre' => $proceso->nombre,
                    'categoria' => $proceso->categoria,
                    'categoria_titulo' => $titulosCategoria->get($proceso->categoria, 'Otros Procesos'),
                    'documentos' => (int) $documentosProceso->count(),
                    'activos' => (int) $documentosProceso->where('estado', 'ACTIVO')->count(),
                    'en_revision' => (int) $documentosProceso->filter(function ($documento) use ($ultimosEstados) {
                        return ($ultimosEstados->get($documento->id)?->estado ?? null) === 'EN_REVISION';
                    })->count(),
                    'tipos_documento' => $tiposResumen,
                ],
            ];
        })->all();
    }

    public function obtenerListadoMaestro(
        bool $puedeVerInactivos,
        bool $puedeVerTodo,
        ?string $centroCostoCodigo
    ): Collection {
        $categorias = $this->obtenerCategorias();
        $titulosCategoria = collect($categorias)
            ->mapWithKeys(fn ($categoria) => [$categoria['codigo'] => $categoria['titulo']]);

        $conexion = DB::connection($this->obtenerConexion());
        $ultimaVersionAprobada = $conexion->table('sig_documento_versiones as version_aprobada')
            ->select('version_aprobada.documento_id', DB::raw('MAX(version_aprobada.id) as version_id'))
            ->where('version_aprobada.estado', 'APROBADO')
            ->groupBy('version_aprobada.documento_id');

        $documentosQuery = $conexion->table('sig_documentos')
            ->leftJoin('sig_procesos as proceso', 'proceso.id', '=', 'sig_documentos.id_proceso')
            ->leftJoin('sig_ubicaciones as ubicacion_documento', 'ubicacion_documento.id', '=', 'sig_documentos.id_ubicacion')
            ->leftJoinSub($ultimaVersionAprobada, 'ultima_version_aprobada', function ($join) {
                $join->on('ultima_version_aprobada.documento_id', '=', 'sig_documentos.id');
            })
            ->leftJoin('sig_documento_versiones as version', 'version.id', '=', 'ultima_version_aprobada.version_id')
            ->leftJoin('sig_ubicaciones as ubicacion_elabora', 'ubicacion_elabora.id', '=', 'version.id_elabora')
            ->leftJoin('sig_ubicaciones as ubicacion_revisa', 'ubicacion_revisa.id', '=', 'version.id_revisa')
            ->leftJoin('sig_ubicaciones as ubicacion_aprueba', 'ubicacion_aprueba.id', '=', 'version.id_aprueba')
            ->whereNotNull('sig_documentos.codigo')
            ->where('sig_documentos.codigo', '!=', '');

        $this->aplicarFiltroVisibilidadDocumentos($documentosQuery, $puedeVerTodo, $centroCostoCodigo);

        if (! $puedeVerInactivos) {
            $documentosQuery->where('sig_documentos.estado', 'ACTIVO');
        }

        $documentos = $documentosQuery
            ->orderByRaw("
                CASE proceso.categoria
                    WHEN 'G' THEN 1
                    WHEN 'M' THEN 2
                    WHEN 'A' THEN 3
                    WHEN 'O' THEN 4
                    ELSE 99
                END
            ")
            ->orderBy('proceso.nombre')
            ->orderBy('sig_documentos.codigo')
            ->orderBy('sig_documentos.nombre')
            ->get([
                'sig_documentos.codigo',
                'sig_documentos.nombre',
                'proceso.categoria as categoria_codigo',
                'proceso.nombre as proceso',
                'version.version',
                DB::raw('COALESCE(version.fecha_aprobacion, version.fecha_elaboracion) as fecha_emision'),
                'ubicacion_elabora.nombre as elaboro',
                'ubicacion_revisa.nombre as reviso',
                'ubicacion_aprueba.nombre as aprueba',
                'ubicacion_documento.nombre as ubicacion',
            ]);

        return $documentos->map(function ($documento) use ($titulosCategoria) {
            $categoriaCodigo = $documento->categoria_codigo ?: 'O';
            return [
                'categoria' => $titulosCategoria->get($categoriaCodigo, 'Otros Procesos'),
                'proceso' => $documento->proceso ?? '',
                'codigo' => $documento->codigo,
                'nombre' => $documento->nombre,
                'version' => $documento->version,
                'fecha_emision' => $documento->fecha_emision
                    ? substr((string) $documento->fecha_emision, 0, 10)
                    : null,
                'elaboro' => $documento->elaboro,
                'reviso' => $documento->reviso,
                'aprueba' => $documento->aprueba,
                'ubicacion' => $documento->ubicacion,
            ];
        })->values();
    }

    public function obtenerFirmaListadoMaestro(
        bool $puedeVerInactivos,
        bool $puedeVerTodo,
        ?string $centroCostoCodigo
    ): string {
        $conexion = DB::connection($this->obtenerConexion());

        $documentosQuery = $conexion->table('sig_documentos')
            ->whereNotNull('sig_documentos.codigo')
            ->where('sig_documentos.codigo', '!=', '');

        $this->aplicarFiltroVisibilidadDocumentos($documentosQuery, $puedeVerTodo, $centroCostoCodigo);

        if (! $puedeVerInactivos) {
            $documentosQuery->where('sig_documentos.estado', 'ACTIVO');
        }

        $documentosStats = (clone $documentosQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(MAX(sig_documentos.id), 0) as max_id')
            ->selectRaw("COALESCE(MAX(sig_documentos.fechamodifica), MAX(sig_documentos.fechacreacion), '1970-01-01 00:00:00') as max_fecha")
            ->first();

        $documentosIds = (clone $documentosQuery)->select('sig_documentos.id');

        $versionesStats = $conexion->table('sig_documento_versiones as version')
            ->joinSub($documentosIds, 'documentos_filtrados', function ($join) {
                $join->on('documentos_filtrados.id', '=', 'version.documento_id');
            })
            ->where('version.estado', 'APROBADO')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(MAX(version.id), 0) as max_id')
            ->selectRaw("COALESCE(MAX(version.fecha_modificacion), MAX(version.fecha_aprobacion), MAX(version.fecha_elaboracion), '1970-01-01 00:00:00') as max_fecha")
            ->first();

        return sha1(json_encode([
            'scope' => [
                'puede_ver_inactivos' => $puedeVerInactivos,
                'puede_ver_todo' => $puedeVerTodo,
                'centro_costo' => $centroCostoCodigo,
            ],
            'documentos' => [
                'total' => (int) ($documentosStats->total ?? 0),
                'max_id' => (int) ($documentosStats->max_id ?? 0),
                'max_fecha' => (string) ($documentosStats->max_fecha ?? ''),
            ],
            'versiones' => [
                'total' => (int) ($versionesStats->total ?? 0),
                'max_id' => (int) ($versionesStats->max_id ?? 0),
                'max_fecha' => (string) ($versionesStats->max_fecha ?? ''),
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    public function obtenerDatosCategoria(
        string $codigoCategoria,
        string $estadoFiltro,
        bool $puedeVerInactivos,
        bool $puedeVerTodo,
        ?string $centroCostoCodigo
    ): array {
        if (! $puedeVerInactivos && $estadoFiltro !== 'activos') {
            $estadoFiltro = 'activos';
        }

        $documentosQuery = Documentos::with([
            'proceso' => function ($query) {
                $query->select('id', 'categoria', 'nombre');
            },
            'tipoDocumento' => function ($query) {
                $query->select('id', 'nombre');
            },
            'ubicacion' => function ($query) {
                $query->select('id', 'nombre');
            },
            'versiones' => function ($query) {
                $query->select('id', 'documento_id', 'version', 'fecha_aprobacion', 'id_elabora', 'id_revisa', 'id_aprueba', 'estado')
                    ->where('estado', 'APROBADO')
                    ->orderByDesc('version')
                    ->limit(1);
            },
        ])
            ->whereHas('proceso', function ($query) use ($codigoCategoria) {
                $query->where('categoria', $codigoCategoria);
            });

        $documentosQuery->whereNotNull('codigo')->where('codigo', '!=', '');
        $this->aplicarFiltroVisibilidadDocumentos($documentosQuery, $puedeVerTodo, $centroCostoCodigo);

        if ($estadoFiltro === 'activos') {
            $documentosQuery->where('estado', 'ACTIVO');
        } elseif ($estadoFiltro === 'inactivos') {
            $documentosQuery->where('estado', 'INACTIVO');
        }

        $documentos = $documentosQuery->get(['id', 'codigo', 'nombre', 'id_proceso', 'id_ubicacion', 'id_tipo_doc', 'estado']);

        $documentoIds = $documentos->pluck('id')->all();
        $ultimosEstados = $documentoIds
          ? DocumentosVersiones::whereIn('documento_id', $documentoIds)
              ->orderByDesc('id')
              ->get(['documento_id', 'estado'])
              ->unique('documento_id')
              ->keyBy('documento_id')
          : collect();
        $enRevisionIds = $ultimosEstados->filter(fn ($v) => $v->estado === 'EN_REVISION')->keys()->all();

        $ubicacionesIds = $documentos->flatMap(function ($documento) {
            $version = $documento->versiones->first();
            if (! $version) {
                return [];
            }

            return [
                trim((string) $version->id_elabora),
                trim((string) $version->id_revisa),
                trim((string) $version->id_aprueba),
            ];
        })->filter()->unique()->all();
        $ubicaciones = $ubicacionesIds
          ? Ubicaciones::whereIn('id', $ubicacionesIds)->get(['id', 'nombre'])->keyBy('id')
          : collect();

        $filas = $documentos->map(function ($documento) use ($ubicaciones, $enRevisionIds) {
            $version = $documento->versiones->first();

            $obtenerNombre = function ($id) use ($ubicaciones) {
                $id = trim((string) $id);
                if ($id === '') {
                    return null;
                }

                return optional($ubicaciones->get($id))->nombre;
            };

            return [
                'id' => $documento->id,
                'codigo' => $documento->codigo,
                'nombre' => $documento->nombre,
                'proceso_id' => $documento->proceso?->id,
                'proceso' => $documento->proceso?->nombre ?? $documento->proceso?->id,
                'tipo_id' => $documento->tipoDocumento?->id,
                'tipo_documento' => $documento->tipoDocumento?->nombre,
                'version' => $version?->version,
                'fecha_aprobacion' => $version?->fecha_aprobacion,
                'elaboro' => $obtenerNombre($version?->id_elabora),
                'reviso' => $obtenerNombre($version?->id_revisa),
                'aprueba' => $obtenerNombre($version?->id_aprueba),
                'ubicacion' => $documento->ubicacion?->nombre,
                'estado' => $documento->estado,
                'en_revision' => in_array($documento->id, $enRevisionIds, true),
            ];
        })->values();

        return [
            'documentos' => $filas,
            'estadoFiltro' => $estadoFiltro,
        ];
    }

    public function obtenerProcesosFormulario(): Collection
    {
        return Procesos::orderBy('nombre')->get(['id', 'nombre', 'categoria', 'abreviatura']);
    }

    public function obtenerTiposDocumentosFormulario(): Collection
    {
        return TiposDocumentos::orderBy('nombre')->get(['id', 'nombre', 'abreviatura']);
    }

    public function obtenerUbicacionesDocumento(): Collection
    {
        return Ubicaciones::orderBy('nombre')->get(['id', 'nombre']);
    }

    public function obtenerCentrosCostosDocumento(int $documentoId): Collection
    {
        return DB::connection($this->obtenerConexion())
            ->table('sig_documentos_centros_costos')
            ->where('documento_id', $documentoId)
            ->get(['centro_costo_codigo', 'centro_costo_nombre']);
    }

    public function guardarCentrosCostosDocumento(int $documentoId, array $centros): void
    {
        $conexion = $this->obtenerConexion();

        DB::connection($conexion)->table('sig_documentos_centros_costos')
            ->where('documento_id', $documentoId)
            ->delete();

        if (empty($centros)) {
            return;
        }

        $rows = [];
        foreach ($centros as $centro) {
            $codigo = $centro['codigo'] ?? null;
            $nombre = $centro['nombre'] ?? null;
            if (! $codigo || ! $nombre) {
                continue;
            }
            $rows[] = [
                'documento_id' => $documentoId,
                'centro_costo_codigo' => $codigo,
                'centro_costo_nombre' => $nombre,
            ];
        }

        if (! empty($rows)) {
            DB::connection($conexion)->table('sig_documentos_centros_costos')->insert($rows);
        }
    }

    public function crearDocumentoNuevo(array $data, int $userId): DocumentosVersiones
    {
        return DB::transaction(function () use ($data, $userId) {
            $documento = new Documentos;
            $documento->codigo = null;
            $documento->nombre = $data['nombre'];
            $documento->descripcion = $data['descripcion'];
            $documento->id_proceso = $data['id_proceso'];
            $documento->id_tipo_doc = $data['id_tipo_doc'];
            $documento->id_ubicacion = $data['id_ubicacion'];
            $documento->estado = 'INACTIVO';
            $documento->usrcreacion = $userId;
            $documento->fechacreacion = now();
            $documento->save();

            $version = new DocumentosVersiones;
            $version->documento_id = $documento->id;
            $version->version = null;
            $version->archivo_url = $data['archivo']->getClientOriginalName();
            $version->paginas = $data['paginas'] ?? null;
            $version->estado = 'EN_REVISION';
            $version->comentario_revision = $data['comentario_revision'] ?? null;
            $version->id_elabora = $data['id_elabora'] ?? null;
            $version->id_revisa = null;
            $version->id_aprueba = null;
            $version->fecha_elaboracion = now();
            $version->fecha_revision = null;
            $version->fecha_aprobacion = null;
            $version->fecha_modificacion = now();
            $version->usrcreacion = $userId;
            $version->save();

            $centrosCostos = $data['centros_costos'] ?? [];
            if (! empty($centrosCostos)) {
                $rows = [];
                foreach ($centrosCostos as $centro) {
                    $codigo = $centro['codigo'] ?? null;
                    $nombre = $centro['nombre'] ?? null;
                    if (! $codigo || ! $nombre) {
                        continue;
                    }
                    $rows[] = [
                        'documento_id' => $documento->id,
                        'centro_costo_codigo' => $codigo,
                        'centro_costo_nombre' => $nombre,
                    ];
                }

                if (! empty($rows)) {
                    DB::connection($documento->getConnectionName())
                        ->table('sig_documentos_centros_costos')
                        ->insert($rows);
                }
            }

            return $version;
        });
    }

    private function obtenerConexion(): string
    {
        return (new Documentos)->getConnectionName();
    }

    private function aplicarFiltroVisibilidadDocumentos($documentosQuery, bool $puedeVerTodo, ?string $centroCostoCodigo): void
    {
        if ($puedeVerTodo) {
            return;
        }

        $documentosQuery->where(function ($query) use ($centroCostoCodigo) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('sig_documentos_centros_costos as scc')
                    ->whereColumn('scc.documento_id', 'sig_documentos.id');
            });

            if ($centroCostoCodigo) {
                $query->orWhereExists(function ($sub) use ($centroCostoCodigo) {
                    $sub->select(DB::raw(1))
                        ->from('sig_documentos_centros_costos as scc')
                        ->whereColumn('scc.documento_id', 'sig_documentos.id')
                        ->where('scc.centro_costo_codigo', $centroCostoCodigo);
                });
            }
        });
    }

    public function asignarCodigoDocumento(Documentos $documento, ?int $usuarioId = null): ?string
    {
        if (! empty($documento->codigo)) {
            return $documento->codigo;
        }

        $tipo = $documento->tipoDocumento ?? TiposDocumentos::find($documento->id_tipo_doc, ['id', 'abreviatura']);
        $proceso = $documento->proceso ?? Procesos::find($documento->id_proceso, ['id', 'abreviatura']);
        $abreviaturaTipo = strtoupper(trim((string) ($tipo?->abreviatura ?? '')));
        $abreviaturaProceso = strtoupper(trim((string) ($proceso?->abreviatura ?? '')));

        if ($abreviaturaTipo === '' || $abreviaturaProceso === '') {
            return null;
        }

        $codigo = $this->generarCodigoPorPrefijo($abreviaturaTipo, $abreviaturaProceso);
        $documento->codigo = $codigo;
        $documento->estado = 'ACTIVO';
        $documento->fechamodifica = now();
        if ($usuarioId) {
            $documento->usrmodifica = $usuarioId;
        }
        $documento->save();

        return $codigo;
    }

    private function generarCodigoPorPrefijo(string $abreviaturaTipo, string $abreviaturaProceso): string
    {
        $prefijo = "{$abreviaturaTipo}-{$abreviaturaProceso}-";
        $codigos = Documentos::whereNotNull('codigo')
            ->where('codigo', 'like', $prefijo.'%')
            ->pluck('codigo');

        $max = 0;
        $regex = '/^'.preg_quote($prefijo, '/').'(\\d+)$/';

        foreach ($codigos as $codigo) {
            $codigo = strtoupper(trim((string) $codigo));
            if (preg_match($regex, $codigo, $match)) {
                $numero = (int) $match[1];
                if ($numero > $max) {
                    $max = $numero;
                }
            }
        }

        $secuencia = str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);

        return $prefijo.$secuencia;
    }
}
