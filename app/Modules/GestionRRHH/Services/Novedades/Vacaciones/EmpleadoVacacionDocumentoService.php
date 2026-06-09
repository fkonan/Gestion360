<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Vacaciones;

use App\Services\DocumentalStorageService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoVacacionDocumentoService
{
    private const CONNECTION = 'oracle-360';
    private const CACHE_TTL_SECONDS = 900;
    private const CACHE_TIPO_NOVEDAD_KEY = 'empleados_vacaciones_tipo_novedad_oracle';
    private const CACHE_TIPO_DOCUMENTO_KEY = 'empleados_vacaciones_tipo_documento_oracle';
    private const MAX_FILE_SIZE_KB = 10240;

    public function __construct(
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public static function reglasDocumento(): array
    {
        return [
            'carta' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.self::MAX_FILE_SIZE_KB,
        ];
    }

    public static function mensajesDocumento(): array
    {
        return [
            'carta.required' => 'Debes adjuntar la carta de solicitud de vacaciones.',
            'carta.file' => 'El archivo de carta no es valido.',
            'carta.mimes' => 'La carta debe estar en formato PDF, JPG, JPEG o PNG.',
            'carta.max' => 'La carta no puede superar 10 MB.',
        ];
    }

    public function guardarCarta(string $idNovedad, UploadedFile $carta, string $documentoPersona): array
    {
        $idNovedad = trim($idNovedad);
        $documentoPersona = trim($documentoPersona);
        if ($idNovedad === '' || $documentoPersona === '') {
            return [
                'ok' => false,
                'message' => 'No fue posible asociar la carta de vacaciones al registro.',
            ];
        }

        $tipoDocumentoId = $this->obtenerTipoDocumentoCartaId();
        if ($tipoDocumentoId === null) {
            return [
                'ok' => false,
                'message' => 'No existe un tipo de documento configurado para la carta de vacaciones.',
            ];
        }

        $connection = DB::connection(self::CONNECTION);
        $rutaNueva = null;
        $rutaAnterior = null;

        $connection->beginTransaction();
        try {
            $resultadoCarga = $this->documentalStorage->guardarArchivo(
                archivo: $carta,
                documento: $documentoPersona,
                categoria: 'VACACIONES',
                radicado: $idNovedad,
                nombreBase: 'CARTA_SOLICITUD_VACACIONES',
                year: (int) Carbon::now()->format('Y')
            );

            $rutaNueva = trim((string) ($resultadoCarga['relative_path'] ?? ''));
            if ($rutaNueva === '') {
                throw new RuntimeException('No se genero la ruta relativa de la carta de vacaciones.');
            }

            $registro = $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                ->where('id_novedad', $idNovedad)
                ->where('id_tipo_documento', $tipoDocumentoId)
                ->first(['id', 'ruta_documento']);

            if ($registro) {
                $rutaAnterior = trim((string) ($registro->ruta_documento ?? ''));
                $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                    ->where('id', (string) $registro->id)
                    ->update([
                        'ruta_documento' => $rutaNueva,
                        'fecha_creacion' => now(),
                    ]);
                $idDocumento = (string) $registro->id;
            } else {
                $idDocumento = (string) Str::uuid();
                $connection->table('EMP_NOVEDADES_DOCUMENTOS')
                    ->insert([
                        'id' => $idDocumento,
                        'id_novedad' => $idNovedad,
                        'id_tipo_documento' => $tipoDocumentoId,
                        'ruta_documento' => $rutaNueva,
                        'fecha_creacion' => now(),
                    ]);
            }

            $connection->commit();

            if ($rutaAnterior !== null && $rutaAnterior !== '' && $rutaAnterior !== $rutaNueva) {
                $this->documentalStorage->eliminarSiExiste($rutaAnterior);
            }

            return [
                'ok' => true,
                'guardados' => 1,
                'adjunto' => [
                    'id' => $idDocumento,
                    'id_novedad' => $idNovedad,
                    'id_tipo_documento' => $tipoDocumentoId,
                    'ruta_documento' => $rutaNueva,
                ],
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();

            if (is_string($rutaNueva) && trim($rutaNueva) !== '') {
                $this->documentalStorage->eliminarSiExiste($rutaNueva);
            }

            Log::error('Error guardando carta de vacaciones', [
                'id_novedad' => $idNovedad,
                'documento_persona' => $documentoPersona,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'No fue posible guardar la carta de vacaciones.',
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
            ->get();
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

                    $tipoDocumento = trim((string) ($adjunto->tipo_documento ?? 'CARTA VACACIONES'));
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
                    Log::warning('No fue posible preparar un adjunto de vacaciones para correo.', [
                        'id_novedad' => $idNovedad,
                        'id_documento' => (string) ($adjunto->id ?? ''),
                        'ruta_documento' => $rutaDocumento,
                        'message' => $e->getMessage(),
                    ]);

                    return null;
                }
            })
            ->filter(fn ($adjunto) => is_array($adjunto))
            ->values()
            ->all();
    }

    public function obtenerTipoNovedadVacacionId(): ?string
    {
        return Cache::remember(self::CACHE_TIPO_NOVEDAD_KEY, self::CACHE_TTL_SECONDS, function () {
            $id = DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->where(function ($query) {
                    $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_VACACIONES'])
                        ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['VACACION']);
                })
                ->value('id');

            if (! is_string($id) || trim($id) === '') {
                return null;
            }

            return trim($id);
        });
    }

    private function obtenerTipoDocumentoCartaId(): ?string
    {
        return Cache::remember(self::CACHE_TIPO_DOCUMENTO_KEY, self::CACHE_TTL_SECONDS, function () {
            $tipoNovedadId = $this->obtenerTipoNovedadVacacionId();
            if ($tipoNovedadId === null) {
                return null;
            }

            $tabla = DB::connection(self::CONNECTION)->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD');
            $id = $tabla
                ->where('id_tipo_novedad', $tipoNovedadId)
                ->where(function ($query) {
                    $query->whereRaw("UPPER(NVL(descripcion, '')) = ?", ['CARTA SOLICITUD VACACIONES'])
                        ->orWhereRaw("UPPER(NVL(codigo, '')) = ?", ['DOCUMENTOS-VACACIONES']);
                })
                ->orderBy('descripcion')
                ->value('id');

            if (! is_string($id) || trim($id) === '') {
                $id = $tabla
                    ->where('id_tipo_novedad', $tipoNovedadId)
                    ->where(function ($query) {
                        $query->whereNull('activo')->orWhere('activo', 1);
                    })
                    ->orderBy('descripcion')
                    ->value('id');
            }

            if (! is_string($id) || trim($id) === '') {
                return null;
            }

            return trim($id);
        });
    }

    private function construirNombreAdjuntoCorreo(string $tipoDocumento, ?string $identificacion, ?string $extension = null): string
    {
        $baseNombre = trim($tipoDocumento) !== '' ? trim($tipoDocumento) : 'carta_vacaciones';
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
            $baseNombre = 'carta_vacaciones';
        }

        $extension = strtolower(trim((string) $extension));

        return $extension !== ''
            ? $baseNombre.'.'.$extension
            : $baseNombre;
    }
}

