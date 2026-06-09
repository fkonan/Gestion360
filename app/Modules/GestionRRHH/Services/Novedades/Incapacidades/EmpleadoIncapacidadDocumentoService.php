<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Incapacidades;

use App\Services\DocumentalStorageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoIncapacidadDocumentoService
{
    private const CONNECTION = 'oracle-360';
    private const CACHE_TIPOS_KEY = 'empleados_incapacidades_tipos_documento_oracle';
    private const CACHE_TIPOS_TIPO_ID_KEY = 'empleados_incapacidades_tipo_novedad_oracle';
    private const CACHE_TIPOS_GRUPO_KEY_PREFIX = 'empleados_incapacidades_tipos_documento_grupo_oracle:';
    private const CACHE_CAUSAS_ORACLE_KEY = 'empleados_incapacidades_causas_oracle_indexadas';
    private const CACHE_TTL_SECONDS = 900;
    private const MAX_ADJUNTOS = 8;
    private const MAX_FILE_SIZE_KB = 10240;
    private const GRUPO_GENERAL = 'DOCUMENTOS-INCAPACIDAD';
    private const GRUPO_MATERNIDAD_PATERNIDAD = 'DOCUMENTOS-INCAPACIDAD-MP';
    private const GRUPO_ACCIDENTE_TRANSITO = 'DOCUMENTOS-INCAPACIDAD-AT';

    public function __construct(
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public static function reglasAdjuntos(bool $requeridos = true): array
    {
        return [
            'adjuntos' => ($requeridos ? 'required' : 'nullable').'|array|max:'.self::MAX_ADJUNTOS,
            'adjuntos.*.tipo_documento_id' => 'nullable|string|max:100',
            'adjuntos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:'.self::MAX_FILE_SIZE_KB,
        ];
    }

    public static function mensajesAdjuntos(): array
    {
        return [
            'adjuntos.required' => 'Debe cargar al menos un soporte de la incapacidad.',
            'adjuntos.array' => 'La lista de adjuntos no tiene un formato valido.',
            'adjuntos.max' => 'Solo se permiten hasta '.self::MAX_ADJUNTOS.' adjuntos por incapacidad.',
            'adjuntos.*.archivo.file' => 'El archivo adjunto no es valido.',
            'adjuntos.*.archivo.mimes' => 'Los adjuntos deben ser PDF, JPG, JPEG o PNG.',
            'adjuntos.*.archivo.max' => 'Cada adjunto no puede superar 10 MB.',
            'adjuntos.*.tipo_documento_id.max' => 'El tipo de documento seleccionado no es valido.',
        ];
    }

    public function obtenerTiposDocumento(): array
    {
        return Cache::remember(self::CACHE_TIPOS_KEY, self::CACHE_TTL_SECONDS, function () {
            $query = DB::connection(self::CONNECTION)
                ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
                ->select(['id', 'descripcion', 'id_tipo_novedad', 'activo']);

            $tipoId = $this->obtenerTipoIncapacidadId();
            if ($tipoId !== null) {
                $query->where('id_tipo_novedad', $tipoId);
            }

            return $query
                ->where(function ($subquery) {
                    $subquery->whereNull('activo')
                        ->orWhere('activo', 1);
                })
                ->orderBy('descripcion')
                ->get()
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

    public function obtenerTiposDocumentoPorCausa(string $causaId): array
    {
        $causaId = trim($causaId);
        if ($causaId === '') {
            return [];
        }

        $causa = $this->obtenerCausasOracleIndexadas()[$causaId] ?? null;
        if ($causa === null) {
            return [];
        }

        return $this->obtenerTiposDocumentoPorGrupo($this->resolverGrupoDocumentalPorCausa($causa));
    }

    public function obtenerIdsTiposDocumentoPorCausa(string $causaId): array
    {
        return array_keys($this->obtenerTiposDocumentoPorCausa($causaId));
    }

    public function guardarAdjuntos(string $idNovedad, array $adjuntos): array
    {
        $inicioProceso = microtime(true);
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return [
                'ok' => false,
                'message' => 'No se pudo asociar adjuntos porque la incapacidad no tiene identificador.',
            ];
        }

        $adjuntosNormalizados = $this->normalizarAdjuntos($adjuntos);
        $normalizarMs = (microtime(true) - $inicioProceso) * 1000;
        if ($adjuntosNormalizados === []) {
            return [
                'ok' => true,
                'guardados' => 0,
                'adjuntos' => [],
                'metricas' => [
                    'adjuntos_total' => 0,
                    'normalizar_ms' => round($normalizarMs, 2),
                    'total_ms' => round((microtime(true) - $inicioProceso) * 1000, 2),
                ],
            ];
        }

        $inicioValidacionTipos = microtime(true);
        $tipos = $this->obtenerTiposDocumento();
        if ($tipos === []) {
            return [
                'ok' => false,
                'message' => 'No se encontraron tipos de documento configurados para incapacidades.',
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
        $validarTiposMs = (microtime(true) - $inicioValidacionTipos) * 1000;

        $metricas = [
            'adjuntos_total' => count($adjuntosNormalizados),
            'normalizar_ms' => round($normalizarMs, 2),
            'validar_tipos_ms' => round($validarTiposMs, 2),
            'consultar_novedad_ms' => 0.0,
            'upload_ms_total' => 0.0,
            'db_select_ms_total' => 0.0,
            'db_write_ms_total' => 0.0,
            'peso_total_kb' => 0.0,
            'eliminar_rutas_previas_ms' => 0.0,
        ];

        $connection = DB::connection(self::CONNECTION);
        $registros = [];
        $rutasNuevas = [];
        $rutasAnterioresPendientesEliminar = [];

        $connection->beginTransaction();

        try {
            $inicioMeta = microtime(true);
            $novedadMeta = $this->obtenerMetadatosNovedad($idNovedad);
            $metricas['consultar_novedad_ms'] = (microtime(true) - $inicioMeta) * 1000;
            foreach ($adjuntosNormalizados as $adjunto) {
                /** @var UploadedFile $archivo */
                $archivo = $adjunto['archivo'];
                $tipoDocumentoId = trim((string) $adjunto['tipo_documento_id']);
                $tipoDocumentoNombre = $tipos[$tipoDocumentoId] ?? $tipoDocumentoId;
                $fileSize = (int) ($archivo->getSize() ?? 0);
                $metricas['peso_total_kb'] += $fileSize > 0 ? ($fileSize / 1024) : 0.0;

                $inicioUpload = microtime(true);
                $resultadoCarga = $this->documentalStorage->guardarArchivo(
                    archivo: $archivo,
                    documento: (string) $novedadMeta->id_persona,
                    categoria: 'INCAPACIDADES',
                    radicado: $idNovedad,
                    nombreBase: $tipoDocumentoNombre,
                    year: $this->resolverAnioDocumento($novedadMeta->fecha_creacion ?? null)
                );
                $metricas['upload_ms_total'] += (microtime(true) - $inicioUpload) * 1000;

                $rutaDocumento = trim((string) ($resultadoCarga['relative_path'] ?? ''));
                if ($rutaDocumento === '') {
                    throw new RuntimeException('No se genero la ruta relativa del adjunto documental.');
                }

                $inicioSelect = microtime(true);
                $registroExistente = $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                    ->where('id_novedad', $idNovedad)
                    ->where('id_tipo_documento', $tipoDocumentoId)
                    ->first(['id', 'ruta_documento']);
                $metricas['db_select_ms_total'] += (microtime(true) - $inicioSelect) * 1000;

                $idDocumento = trim((string) ($registroExistente->id ?? ''));
                $rutaAnterior = trim((string) ($registroExistente->ruta_documento ?? ''));

                $inicioWrite = microtime(true);
                if ($idDocumento === '') {
                    $idDocumento = (string) Str::uuid();
                    $connection->table('EMP_NOVEDADES_DOCUMENTOS')->insert([
                        'id' => $idDocumento,
                        'id_novedad' => $idNovedad,
                        'id_tipo_documento' => $tipoDocumentoId,
                        'ruta_documento' => $rutaDocumento,
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
                $metricas['db_write_ms_total'] += (microtime(true) - $inicioWrite) * 1000;

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
            $inicioEliminar = microtime(true);
            $this->documentalStorage->eliminarMultiplesSiExisten($rutasAnterioresPendientesEliminar);
            $metricas['eliminar_rutas_previas_ms'] = (microtime(true) - $inicioEliminar) * 1000;

            $metricas['promedio_upload_ms'] = count($adjuntosNormalizados) > 0
                ? ($metricas['upload_ms_total'] / count($adjuntosNormalizados))
                : 0.0;
            $metricas['total_ms'] = (microtime(true) - $inicioProceso) * 1000;
            foreach ($metricas as $clave => $valor) {
                if (is_float($valor)) {
                    $metricas[$clave] = round($valor, 2);
                }
            }

            return [
                'ok' => true,
                'guardados' => count($registros),
                'adjuntos' => $registros,
                'metricas' => $metricas,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->documentalStorage->eliminarMultiplesSiExisten($rutasNuevas);

            $metricas['total_ms'] = round((microtime(true) - $inicioProceso) * 1000, 2);

            Log::error('Error guardando adjuntos de incapacidad', [
                'id_novedad' => $idNovedad,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'No fue posible guardar los adjuntos de la incapacidad.',
                'error' => $e->getMessage(),
                'metricas' => $metricas,
            ];
        }
    }

    public function obtenerAdjuntosPorNovedad(string $idNovedad): Collection
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return collect();
        }

        return DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS as d')
            ->leftJoin('EMP_TIPOS_DOCUMENTOS_NOVEDAD as t', 't.id', '=', 'd.id_tipo_documento')
            ->where('d.id_novedad', $idNovedad)
            ->select([
                'd.id',
                'd.id_novedad',
                'd.id_tipo_documento',
                'd.ruta_documento',
                'd.fecha_creacion',
                't.descripcion as tipo_documento',
            ])
            ->orderByDesc('d.fecha_creacion')
            ->get()
            ->map(function ($item) {
                $item->ruta_documento = trim((string) ($item->ruta_documento ?? ''));

                return $item;
            });
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

    public function construirAdjuntosCorreoDesdeNovedad(string $idNovedad, ?string $identificacion = null): array
    {
        $idNovedad = trim($idNovedad);
        if ($idNovedad === '') {
            return [];
        }

        $identificacion = trim((string) $identificacion);

        return $this->obtenerAdjuntosPorNovedad($idNovedad)
            ->map(function ($adjunto) use ($idNovedad, $identificacion) {
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
                    Log::warning('No fue posible preparar un adjunto de incapacidad para correo.', [
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

            if ($tipoDocumentoId === '' || ! $archivo instanceof UploadedFile) {
                continue;
            }

            $resultado[] = [
                'tipo_documento_id' => $tipoDocumentoId,
                'archivo' => $archivo,
            ];
        }

        return $resultado;
    }

    private function obtenerMetadatosNovedad(string $idNovedad): object
    {
        $novedad = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES')
            ->where('id', $idNovedad)
            ->select(['id_persona', 'fecha_creacion'])
            ->first();

        if (! $novedad || trim((string) ($novedad->id_persona ?? '')) === '') {
            throw new RuntimeException('No fue posible resolver el documento del empleado para guardar el adjunto de la incapacidad.');
        }

        return $novedad;
    }

    private function obtenerTipoIncapacidadId(): ?string
    {
        return Cache::remember(self::CACHE_TIPOS_TIPO_ID_KEY, self::CACHE_TTL_SECONDS, function () {
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

    public function obtenerTiposDocumentoPorGrupo(string $grupo): array
    {
        $grupo = trim($grupo);
        if ($grupo === '') {
            return [];
        }

        return Cache::remember(self::CACHE_TIPOS_GRUPO_KEY_PREFIX.$grupo, self::CACHE_TTL_SECONDS, function () use ($grupo) {
            $tipoId = $this->obtenerTipoIncapacidadId();
            if ($tipoId === null) {
                return [];
            }

            return DB::connection(self::CONNECTION)
                ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
                ->where('id_tipo_novedad', $tipoId)
                ->where(function ($query) {
                    $query->whereNull('activo')
                        ->orWhere('activo', 1);
                })
                ->whereRaw('UPPER(NVL(codigo, \'\')) = ?', [strtoupper($grupo)])
                ->orderBy('descripcion')
                ->get(['id', 'descripcion'])
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

    private function obtenerCausasOracleIndexadas(): array
    {
        return Cache::remember(self::CACHE_CAUSAS_ORACLE_KEY, self::CACHE_TTL_SECONDS, function () {
            return DB::connection(self::CONNECTION)
                ->table('EMP_CAUSAS_INCAPACIDAD')
                ->where('estado', 'ACTIVO')
                ->get(['id', 'causa'])
                ->mapWithKeys(function ($item) {
                    $id = trim((string) ($item->id ?? ''));
                    $causa = trim((string) ($item->causa ?? ''));

                    if ($id === '' || $causa === '') {
                        return [];
                    }

                    return [$id => $causa];
                })
                ->all();
        });
    }

    private function resolverGrupoDocumentalPorCausa(string $causa): string
    {
        return match ($this->normalizarEtiqueta($causa)) {
            'LICENCIA DE MATERNIDAD', 'LICENCIA DE PATERNIDAD' => self::GRUPO_MATERNIDAD_PATERNIDAD,
            'ACCIDENTE DE TRANSITO COMUN' => self::GRUPO_ACCIDENTE_TRANSITO,
            default => self::GRUPO_GENERAL,
        };
    }

    private function normalizarEtiqueta(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $sinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $sinAcentos = $sinAcentos !== false ? $sinAcentos : $texto;

        return mb_strtoupper(trim($sinAcentos), 'UTF-8');
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
        $baseNombre = trim($tipoDocumento) !== '' ? trim($tipoDocumento) : 'adjunto_incapacidad';
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
            $baseNombre = 'adjunto_incapacidad';
        }

        $extension = strtolower(trim((string) $extension));

        return $extension !== ''
            ? $baseNombre.'.'.$extension
            : $baseNombre;
    }
}

