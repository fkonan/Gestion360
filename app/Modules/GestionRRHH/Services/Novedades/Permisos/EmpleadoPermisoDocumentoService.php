<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Permisos;

use App\Services\DocumentalStorageService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoPermisoDocumentoService
{
    private const CONNECTION = 'oracle-360';
    private const CACHE_TIPOS_KEY = 'empleados_permisos_tipos_documento_v2';
    private const CACHE_TIPO_PERMISO_ID_KEY = 'empleados_permisos_tipo_novedad_oracle_v2';
    private const CACHE_TABLA_TIPOS_KEY = 'empleados_permisos_tabla_tipos_documento_v2';
    private const CACHE_TTL_SECONDS = 900;
    private const MAX_ADJUNTOS = 5;
    private const MAX_FILE_SIZE_KB = 10240;
    private const TIPOS_TABLE_CANDIDATES = [
        'EMP_TIPOS_DOCUMENTOS_NOVEDAD',
    ];

    public function __construct(
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public static function reglasAdjuntos(): array
    {
        return [
            'adjuntos' => 'nullable|array|max:'.self::MAX_ADJUNTOS,
            'adjuntos.*.tipo_documento_id' => 'nullable|string|max:144',
            'adjuntos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:'.self::MAX_FILE_SIZE_KB,
        ];
    }

    public static function mensajesAdjuntos(): array
    {
        return [
            'adjuntos.array' => 'La lista de adjuntos no tiene un formato valido.',
            'adjuntos.max' => 'Solo se permiten hasta '.self::MAX_ADJUNTOS.' adjuntos por permiso.',
            'adjuntos.*.archivo.file' => 'El archivo adjunto no es valido.',
            'adjuntos.*.archivo.mimes' => 'Los adjuntos deben ser PDF, JPG, JPEG o PNG.',
            'adjuntos.*.archivo.max' => 'Cada adjunto no puede superar 10 MB.',
            'adjuntos.*.tipo_documento_id.max' => 'El tipo de documento seleccionado no es valido.',
        ];
    }

    public function obtenerTiposDocumento(): array
    {
        return Cache::remember(self::CACHE_TIPOS_KEY, self::CACHE_TTL_SECONDS, function () {
            $tablaTipos = $this->resolverTablaTiposDocumento();
            if (! $tablaTipos) {
                return [];
            }

            $query = DB::connection(self::CONNECTION)
                ->table($tablaTipos)
                ->orderBy('descripcion');

            $query->select([
                'id',
                'descripcion',
                'id_tipo_novedad',
                'activo',
            ]);

            $tipos = $query->get();

            $tipoPermisoId = $this->obtenerTipoPermisoId();

            return $tipos
                ->filter(function ($item) use ($tipoPermisoId, $tablaTipos) {
                    $activo = trim((string) ($item->activo ?? ''));
                    if ($activo !== '' && $activo !== '1') {
                        return false;
                    }

                    $idTipoNovedad = trim((string) ($item->id_tipo_novedad ?? ''));
                    if ($tipoPermisoId !== null && $idTipoNovedad === $tipoPermisoId) {
                        return true;
                    }

                    return false;
                })
                ->mapWithKeys(function ($item) {
                    $id = trim((string) ($item->id ?? ''));
                    $descripcion = trim((string) ($item->descripcion ?? ''));

                    if ($id === '' || $descripcion === '') {
                        return [];
                    }

                    return [$id => $descripcion];
                })
                ->all();
        });
    }

    public function obtenerIdsTiposDocumento(): array
    {
        return array_keys($this->obtenerTiposDocumento());
    }

    public function guardarAdjuntos(string $idNovedad, array $adjuntos): array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return [
                'ok' => false,
                'message' => 'No se pudo asociar adjuntos porque el permiso no tiene identificador.',
            ];
        }

        $adjuntosNormalizados = $this->normalizarAdjuntos($adjuntos);
        if ($adjuntosNormalizados === []) {
            return [
                'ok' => true,
                'guardados' => 0,
            ];
        }

        $tipos = $this->obtenerTiposDocumento();
        if ($tipos === []) {
            return [
                'ok' => false,
                'message' => 'No se encontraron tipos de documento configurados para adjuntos.',
            ];
        }

        $erroresTipo = [];
        foreach ($adjuntosNormalizados as $indice => $adjunto) {
            $tipoDocumentoId = trim((string) $adjunto['tipo_documento_id']);
            if (! isset($tipos[$tipoDocumentoId])) {
                $erroresTipo[] = 'Adjunto #'.($indice + 1).': tipo de documento no valido.';
            }
        }

        if ($erroresTipo !== []) {
            return [
                'ok' => false,
                'message' => implode(' ', $erroresTipo),
            ];
        }

        $registros = [];
        $rutasNuevas = [];
        $rutasAnterioresPendientesEliminar = [];
        $connection = DB::connection(self::CONNECTION);

        $connection->beginTransaction();

        try {
            $novedadMeta = $this->obtenerMetadatosNovedad($idNovedad);
            foreach ($adjuntosNormalizados as $adjunto) {
                /** @var UploadedFile $archivo */
                $archivo = $adjunto['archivo'];
                $tipoDocumentoId = trim((string) $adjunto['tipo_documento_id']);
                $tipoDocumentoNombre = $tipos[$tipoDocumentoId] ?? $tipoDocumentoId;
                $resultadoCarga = $this->documentalStorage->guardarArchivo(
                    archivo: $archivo,
                    documento: (string) $novedadMeta->id_persona,
                    categoria: 'PERMISOS',
                    radicado: $idNovedad,
                    nombreBase: $tipoDocumentoNombre,
                    year: $this->resolverAnioDocumento($novedadMeta->fecha_creacion ?? null)
                );

                $rutaDocumento = trim((string) ($resultadoCarga['relative_path'] ?? ''));
                if ($rutaDocumento === '') {
                    throw new RuntimeException('No se genero la ruta relativa del adjunto documental.');
                }

                $registroExistente = $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                    ->where('id_novedad', $idNovedad)
                    ->where('id_tipo_documento', $tipoDocumentoId)
                    ->first(['id', 'ruta_documento']);

                $idDocumento = trim((string) ($registroExistente->id ?? ''));
                $rutaAnterior = trim((string) ($registroExistente->ruta_documento ?? ''));
                if ($idDocumento === '') {
                    $idDocumento = (string) Str::uuid();
                    $connection->table('EMP_NOVEDADES_DOCUMENTOS')->insert([
                        'id' => $idDocumento,
                        'id_novedad' => $idNovedad,
                        'ruta_documento' => $rutaDocumento,
                        'id_tipo_documento' => $tipoDocumentoId,
                        'fecha_creacion' => now(),
                    ]);
                    $rutasNuevas[] = $rutaDocumento;
                } else {
                    if ($rutaAnterior !== '' && $rutaAnterior !== $rutaDocumento) {
                        $rutasAnterioresPendientesEliminar[] = $rutaAnterior;
                        $rutasNuevas[] = $rutaDocumento;
                    }

                    $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                        ->where('id', $idDocumento)
                        ->update([
                            'ruta_documento' => $rutaDocumento,
                            'fecha_creacion' => now(),
                        ]);
                }

                $registros[] = [
                    'id' => $idDocumento,
                    'id_novedad' => $idNovedad,
                    'ruta_documento' => $rutaDocumento,
                    'id_tipo_documento' => $tipoDocumentoId,
                    'tipo_documento' => $tipoDocumentoNombre,
                    'fuente' => 'sftp',
                ];
            }

            $connection->commit();
            $this->documentalStorage->eliminarMultiplesSiExisten($rutasAnterioresPendientesEliminar);

            return [
                'ok' => true,
                'guardados' => count($registros),
                'adjuntos' => $registros,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->documentalStorage->eliminarMultiplesSiExisten($rutasNuevas);

            Log::error('Error guardando adjuntos de permiso', [
                'id_novedad' => $idNovedad,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'No fue posible guardar los adjuntos del permiso.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function obtenerAdjuntosPorNovedad(string $idNovedad): Collection
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return collect();
        }

        $query = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS as d')
            ->where('d.id_novedad', $idNovedad)
            ->select([
                'd.id',
                'd.id_novedad',
                'd.id_tipo_documento',
                'd.ruta_documento',
                'd.fecha_creacion',
            ])
            ->orderByDesc('d.fecha_creacion');

        $tablaTipos = $this->resolverTablaTiposDocumento();
        if ($tablaTipos) {
            $query->leftJoin($tablaTipos.' as t', 't.id', '=', 'd.id_tipo_documento')
                ->addSelect('t.descripcion as tipo_documento');
        }

        return $query->get()->map(function ($item) {
            $item->id = trim((string) ($item->id ?? ''));
            $item->id_novedad = trim((string) ($item->id_novedad ?? ''));
            $item->id_tipo_documento = trim((string) ($item->id_tipo_documento ?? ''));
            $item->ruta_documento = trim((string) ($item->ruta_documento ?? ''));
            $item->tipo_documento = trim((string) ($item->tipo_documento ?? ''));

            if ($item->tipo_documento === '') {
                $item->tipo_documento = $item->id_tipo_documento;
            }

            return $item;
        });
    }

    public function obtenerConteoAdjuntosPorNovedades(array $idsNovedad): array
    {
        $ids = collect($idsNovedad)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS')
            ->selectRaw('id_novedad, COUNT(*) as total')
            ->whereIn('id_novedad', $ids)
            ->groupBy('id_novedad')
            ->get()
            ->mapWithKeys(function ($item) {
                $idNovedad = trim((string) ($item->id_novedad ?? ''));
                if ($idNovedad === '') {
                    return [];
                }

                return [$idNovedad => (int) ($item->total ?? 0)];
            })
            ->all();
    }

    public function obtenerAdjuntosPorNovedades(array $idsNovedad): array
    {
        $ids = collect($idsNovedad)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $query = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS as d')
            ->whereIn('d.id_novedad', $ids)
            ->select([
                'd.id',
                'd.id_novedad',
                'd.id_tipo_documento',
                'd.ruta_documento',
                'd.fecha_creacion',
            ])
            ->orderByDesc('d.fecha_creacion');

        $tablaTipos = $this->resolverTablaTiposDocumento();
        if ($tablaTipos) {
            $query->leftJoin($tablaTipos.' as t', 't.id', '=', 'd.id_tipo_documento')
                ->addSelect('t.descripcion as tipo_documento');
        }

        $resultado = [];

        foreach ($query->get() as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            if (! isset($resultado[$idNovedad])) {
                $resultado[$idNovedad] = [];
            }

            $rutaDocumento = trim((string) ($item->ruta_documento ?? ''));

            $resultado[$idNovedad][] = [
                'id_documento' => trim((string) ($item->id ?? '')),
                'id_tipo_documento' => trim((string) ($item->id_tipo_documento ?? '')),
                'tipo_documento' => trim((string) ($item->tipo_documento ?? '')),
                'ruta_documento' => $rutaDocumento,
                'fecha_creacion' => trim((string) ($item->fecha_creacion ?? '')),
            ];
        }

        return $resultado;
    }

    public function obtenerAdjuntoPorId(string $idDocumento): ?object
    {
        $idDocumento = trim($idDocumento);
        if ($idDocumento === '') {
            return null;
        }

        $query = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS as d')
            ->where('d.id', $idDocumento)
            ->select([
                'd.id',
                'd.id_novedad',
                'd.id_tipo_documento',
                'd.ruta_documento',
                'd.fecha_creacion',
            ]);

        $tablaTipos = $this->resolverTablaTiposDocumento();
        if ($tablaTipos) {
            $query->leftJoin($tablaTipos.' as t', 't.id', '=', 'd.id_tipo_documento')
                ->addSelect('t.descripcion as tipo_documento');
        }

        $documento = $query->first();
        if (! $documento) {
            return null;
        }

        $documento->id = trim((string) ($documento->id ?? ''));
        $documento->id_novedad = trim((string) ($documento->id_novedad ?? ''));
        $documento->id_tipo_documento = trim((string) ($documento->id_tipo_documento ?? ''));
        $documento->ruta_documento = trim((string) ($documento->ruta_documento ?? ''));
        $documento->tipo_documento = trim((string) ($documento->tipo_documento ?? ''));

        if ($documento->tipo_documento === '') {
            $documento->tipo_documento = $documento->id_tipo_documento;
        }

        return $documento;
    }

    public function extraerAdjuntosDesdeRequest(Request $request): array
    {
        $adjuntosInput = $request->input('adjuntos', []);
        $adjuntosFiles = $request->file('adjuntos', []);

        if (! is_array($adjuntosInput)) {
            $adjuntosInput = [];
        }

        if (! is_array($adjuntosFiles)) {
            $adjuntosFiles = [];
        }

        $indices = collect(array_merge(array_keys($adjuntosInput), array_keys($adjuntosFiles)))
            ->unique()
            ->values()
            ->all();

        $resultado = [];
        foreach ($indices as $indice) {
            $filaInput = is_array($adjuntosInput[$indice] ?? null) ? $adjuntosInput[$indice] : [];
            $filaFile = is_array($adjuntosFiles[$indice] ?? null) ? $adjuntosFiles[$indice] : [];

            $resultado[] = [
                'tipo_documento_id' => $filaInput['tipo_documento_id'] ?? null,
                'archivo' => $filaFile['archivo'] ?? null,
            ];
        }

        return $resultado;
    }

    public function construirAdjuntosCorreo(array $adjuntos, ?string $identificacion = null): array
    {
        $tipos = $this->obtenerTiposDocumento();
        $identificacion = trim((string) $identificacion);

        return collect($this->normalizarAdjuntos($adjuntos))
            ->map(function (array $adjunto, int $indice) use ($tipos) {
                /** @var UploadedFile $archivo */
                $archivo = $adjunto['archivo'];
                if (! $archivo->isValid()) {
                    return null;
                }

                $nombreOriginal = trim((string) $archivo->getClientOriginalName());
                $tipoDocumentoId = trim((string) ($adjunto['tipo_documento_id'] ?? ''));
                $tipoDocumento = trim((string) ($tipos[$tipoDocumentoId] ?? ''));

                if ($nombreOriginal === '') {
                    $extension = trim((string) $archivo->getClientOriginalExtension());
                    $baseNombre = $tipoDocumento !== '' ? $tipoDocumento : 'adjunto_permiso_'.($indice + 1);
                    $nombreOriginal = Str::of($baseNombre)
                        ->ascii()
                        ->replaceMatches('/[^A-Za-z0-9]+/', '_')
                        ->trim('_')
                        ->value();

                    if ($nombreOriginal === '') {
                        $nombreOriginal = 'adjunto_permiso_'.($indice + 1);
                    }

                    if ($extension !== '') {
                        $nombreOriginal .= '.'.$extension;
                    }
                }

                return [
                    'tipo_documento_id' => $tipoDocumentoId,
                    'path' => $archivo->getRealPath(),
                    'name' => str_replace(["\r", "\n"], '', $nombreOriginal),
                    'mime' => trim((string) ($archivo->getMimeType() ?? '')),
                ];
            })
            ->map(function (?array $adjunto) use ($tipos, $identificacion) {
                if (! is_array($adjunto)) {
                    return null;
                }

                $tipoDocumentoId = trim((string) ($adjunto['tipo_documento_id'] ?? ''));
                $tipoDocumento = trim((string) ($tipos[$tipoDocumentoId] ?? ''));
                $path = trim((string) ($adjunto['path'] ?? ''));

                if ($path === '') {
                    return null;
                }

                $extension = pathinfo((string) ($adjunto['name'] ?? ''), PATHINFO_EXTENSION);
                $adjunto['name'] = $this->construirNombreAdjuntoCorreo(
                    tipoDocumento: $tipoDocumento,
                    identificacion: $identificacion,
                    extension: $extension
                );

                return $adjunto;
            })
            ->filter(fn ($adjunto) => is_array($adjunto) && trim((string) ($adjunto['path'] ?? '')) !== '')
            ->values()
            ->all();
    }

    public function construirAdjuntosCorreoDesdeNovedad(string $idNovedad, ?string $identificacion = null): array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return [];
        }

        $identificacion = trim((string) $identificacion);

        return $this->obtenerAdjuntosPorNovedad($idNovedad)
            ->map(function ($adjunto) use ($identificacion) {
                $rutaDocumento = trim((string) ($adjunto->ruta_documento ?? ''));
                if ($rutaDocumento === '') {
                    return null;
                }

                try {
                    $stream = $this->documentalStorage->abrirStream($rutaDocumento);
                    try {
                        $contenido = stream_get_contents($stream);
                    } finally {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                    }

                    if (! is_string($contenido) || $contenido === '') {
                        return null;
                    }

                    $tipoDocumento = trim((string) ($adjunto->tipo_documento ?? ''));
                    $extension = pathinfo($this->documentalStorage->obtenerNombreArchivo($rutaDocumento), PATHINFO_EXTENSION);

                    return [
                        'data_base64' => base64_encode($contenido),
                        'name' => $this->construirNombreAdjuntoCorreo(
                            tipoDocumento: $tipoDocumento,
                            identificacion: $identificacion,
                            extension: $extension
                        ),
                        'mime' => $this->documentalStorage->obtenerMimeType($rutaDocumento),
                    ];
                } catch (\Throwable $e) {
                    Log::warning('No fue posible preparar un adjunto de permiso para correo.', [
                        'id_novedad' => $idNovedad,
                        'id_documento' => (string) ($adjunto->id ?? ''),
                        'ruta_documento' => $rutaDocumento,
                        'message' => $e->getMessage(),
                    ]);

                    return null;
                }
            })
            ->filter(fn ($adjunto) => is_array($adjunto) && trim((string) ($adjunto['name'] ?? '')) !== '')
            ->values()
            ->all();
    }

    private function normalizarAdjuntos(array $adjuntos): array
    {
        $resultado = [];

        foreach ($adjuntos as $adjunto) {
            if (! is_array($adjunto)) {
                continue;
            }

            $tipoDocumentoId = trim((string) ($adjunto['tipo_documento_id'] ?? ''));
            $archivo = $adjunto['archivo'] ?? null;

            if (! $archivo instanceof UploadedFile) {
                continue;
            }

            $resultado[] = [
                'tipo_documento_id' => $tipoDocumentoId,
                'archivo' => $archivo,
            ];
        }

        return $resultado;
    }

    private function resolverTablaTiposDocumento(): ?string
    {
        return Cache::remember(self::CACHE_TABLA_TIPOS_KEY, self::CACHE_TTL_SECONDS, function () {
            foreach (self::TIPOS_TABLE_CANDIDATES as $tabla) {
                try {
                    DB::connection(self::CONNECTION)
                        ->table($tabla)
                        ->select('id')
                        ->limit(1)
                        ->get();

                    return $tabla;
                } catch (\Throwable) {
                    continue;
                }
            }

            return null;
        });
    }

    private function obtenerTipoPermisoId(): ?string
    {
        return Cache::remember(self::CACHE_TIPO_PERMISO_ID_KEY, self::CACHE_TTL_SECONDS, function () {
            $id = DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->where(function ($query) {
                    $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_PERMISOS'])
                        ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['PERMISO']);
                })
                ->value('id');

            if (! is_string($id) || trim($id) === '') {
                return null;
            }

            return trim($id);
        });
    }

    private function obtenerMetadatosNovedad(string $idNovedad): object
    {
        $novedad = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES')
            ->where('id', $idNovedad)
            ->select(['id_persona', 'fecha_creacion'])
            ->first();

        if (! $novedad || trim((string) ($novedad->id_persona ?? '')) === '') {
            throw new RuntimeException('No fue posible resolver el documento del empleado para guardar el adjunto del permiso.');
        }

        return $novedad;
    }

    private function resolverAnioDocumento(mixed $fecha): int
    {
        try {
            return (int) Carbon::parse($fecha)->format('Y');
        } catch (\Throwable) {
            return (int) now()->format('Y');
        }
    }

    private function construirNombreAdjuntoCorreo(string $tipoDocumento, ?string $identificacion, ?string $extension = null): string
    {
        $baseNombre = trim($tipoDocumento) !== '' ? trim($tipoDocumento) : 'adjunto_permiso';
        $identificacion = trim((string) $identificacion);

        if ($identificacion !== '') {
            $baseNombre .= '_'.$identificacion;
        }

        $baseNombre = Str::of($baseNombre)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($baseNombre === '') {
            $baseNombre = 'adjunto_permiso';
        }

        $extension = strtolower(trim((string) $extension));

        return $extension !== ''
            ? $baseNombre.'.'.$extension
            : $baseNombre;
    }

}

