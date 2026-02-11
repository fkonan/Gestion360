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

        if (! $puedeVerTodo) {
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
            $version->id_revisa = $data['id_revisa'] ?? null;
            $version->id_aprueba = $data['id_aprueba'] ?? null;
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
