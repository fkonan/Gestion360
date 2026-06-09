<?php

namespace App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes;

use App\Services\DocumentalStorageService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoPermisoPermanenteDocumentoService
{
    private const CONNECTION = 'oracle-360';
    private const CACHE_TTL_SECONDS = 900;
    private const CACHE_TIPO_NOVEDAD_KEY = 'empleados_perm_permanentes_tipo_novedad_oracle';
    private const CACHE_TIPO_DOCS_KEY = 'empleados_perm_permanentes_tipos_documento_oracle';
    private const MAX_FILE_SIZE_KB = 10240;
    private const DOC_CARTA_DESCRIPCION = 'CARTA SOLICITUD PERMISO PERMANENTE';
    private const DOC_SOPORTE_DESCRIPCION = 'DOCUMENTO SOPORTE PERMISO PERMANENTE';
    private const DOC_CARTA_CODIGO = 'DOCUMENTOS-PERMISO-PERMANENTE-CARTA';
    private const DOC_SOPORTE_CODIGO = 'DOCUMENTOS-PERMISO-PERMANENTE-SOPORTE';

    public function __construct(
        private readonly DocumentalStorageService $documentalStorage
    ) {}

    public static function reglasDocumentos(): array
    {
        return [
            'carta_solicitud' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.self::MAX_FILE_SIZE_KB,
            'documento_soporte' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.self::MAX_FILE_SIZE_KB,
        ];
    }

    public static function mensajesDocumentos(): array
    {
        return [
            'carta_solicitud.required' => 'Debes adjuntar la carta de solicitud del permiso permanente.',
            'carta_solicitud.file' => 'La carta de solicitud no es valida.',
            'carta_solicitud.mimes' => 'La carta de solicitud debe estar en formato PDF, JPG, JPEG o PNG.',
            'carta_solicitud.max' => 'La carta de solicitud no puede superar 10 MB.',
            'documento_soporte.required' => 'Debes adjuntar el documento soporte del permiso permanente.',
            'documento_soporte.file' => 'El documento soporte no es valido.',
            'documento_soporte.mimes' => 'El documento soporte debe estar en formato PDF, JPG, JPEG o PNG.',
            'documento_soporte.max' => 'El documento soporte no puede superar 10 MB.',
        ];
    }

    public function guardarDocumentos(
        string $idNovedad,
        UploadedFile $cartaSolicitud,
        UploadedFile $documentoSoporte,
        string $documentoPersona
    ): array {
        $idNovedad = trim($idNovedad);
        $documentoPersona = trim($documentoPersona);
        if ($idNovedad === '' || $documentoPersona === '') {
            return [
                'ok' => false,
                'message' => 'No fue posible asociar los documentos del permiso permanente.',
            ];
        }

        $tipos = $this->obtenerIdsTiposDocumentoRequeridos();
        $idTipoCarta = trim((string) ($tipos['carta_solicitud'] ?? ''));
        $idTipoSoporte = trim((string) ($tipos['documento_soporte'] ?? ''));
        if ($idTipoCarta === '' || $idTipoSoporte === '') {
            return [
                'ok' => false,
                'message' => 'No existe configuracion de tipos de documento para permiso permanente.',
            ];
        }

        $connection = DB::connection(self::CONNECTION);
        $connection->beginTransaction();
        $rutasNuevas = [];
        $rutasAnteriores = [];
        $guardados = [];

        try {
            $guardados[] = $this->guardarDocumentoPorTipo(
                idNovedad: $idNovedad,
                idTipoDocumento: $idTipoCarta,
                archivo: $cartaSolicitud,
                documentoPersona: $documentoPersona,
                nombreBase: 'CARTA_SOLICITUD_PERMISO_PERMANENTE',
                rutasNuevas: $rutasNuevas,
                rutasAnteriores: $rutasAnteriores
            );

            $guardados[] = $this->guardarDocumentoPorTipo(
                idNovedad: $idNovedad,
                idTipoDocumento: $idTipoSoporte,
                archivo: $documentoSoporte,
                documentoPersona: $documentoPersona,
                nombreBase: 'DOCUMENTO_SOPORTE_PERMISO_PERMANENTE',
                rutasNuevas: $rutasNuevas,
                rutasAnteriores: $rutasAnteriores
            );

            $connection->commit();

            foreach ($rutasAnteriores as $rutaAnterior) {
                if ($rutaAnterior !== '' && ! in_array($rutaAnterior, $rutasNuevas, true)) {
                    $this->documentalStorage->eliminarSiExiste($rutaAnterior);
                }
            }

            return [
                'ok' => true,
                'guardados' => count($guardados),
                'adjuntos' => $guardados,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();

            foreach ($rutasNuevas as $rutaNueva) {
                if ($rutaNueva !== '') {
                    $this->documentalStorage->eliminarSiExiste($rutaNueva);
                }
            }

            Log::error('Error guardando documentos de permiso permanente', [
                'id_novedad' => $idNovedad,
                'documento_persona' => $documentoPersona,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'No fue posible guardar los documentos del permiso permanente.',
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

                    $tipoDocumento = trim((string) ($adjunto->tipo_documento ?? 'ADJUNTO'));
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
                    Log::warning('No fue posible preparar un adjunto de permiso permanente para correo.', [
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

    public function obtenerTipoNovedadPermisoPermanenteId(): ?string
    {
        return Cache::remember(self::CACHE_TIPO_NOVEDAD_KEY, self::CACHE_TTL_SECONDS, function () {
            $id = DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_TIPO')
                ->where(function ($query) {
                    $query->whereRaw("UPPER(NVL(tabla, '')) = ?", ['EMP_PERMISOS_PERMANENTES'])
                        ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['PERMISO_PERMANENTE'])
                        ->orWhereRaw("UPPER(NVL(descripcion, '')) = ?", ['PERMISO PERMANENTE']);
                })
                ->value('id');

            if (! is_string($id) || trim($id) === '') {
                return null;
            }

            return trim($id);
        });
    }

    public function obtenerIdsTiposDocumentoRequeridos(): array
    {
        return Cache::remember(self::CACHE_TIPO_DOCS_KEY, self::CACHE_TTL_SECONDS, function () {
            $tipoNovedadId = $this->obtenerTipoNovedadPermisoPermanenteId();
            if ($tipoNovedadId === null) {
                return [];
            }

            $registros = DB::connection(self::CONNECTION)
                ->table('EMP_TIPOS_DOCUMENTOS_NOVEDAD')
                ->where('id_tipo_novedad', $tipoNovedadId)
                ->get(['id', 'descripcion', 'codigo']);

            $ids = [
                'carta_solicitud' => '',
                'documento_soporte' => '',
            ];

            foreach ($registros as $registro) {
                $descripcion = strtoupper(trim((string) ($registro->descripcion ?? '')));
                $codigo = strtoupper(trim((string) ($registro->codigo ?? '')));
                $id = trim((string) ($registro->id ?? ''));
                if ($id === '') {
                    continue;
                }

                if (
                    $descripcion === self::DOC_CARTA_DESCRIPCION
                    || $codigo === self::DOC_CARTA_CODIGO
                ) {
                    $ids['carta_solicitud'] = $id;
                    continue;
                }

                if (
                    $descripcion === self::DOC_SOPORTE_DESCRIPCION
                    || $codigo === self::DOC_SOPORTE_CODIGO
                ) {
                    $ids['documento_soporte'] = $id;
                    continue;
                }
            }

            return $ids;
        });
    }

    private function guardarDocumentoPorTipo(
        string $idNovedad,
        string $idTipoDocumento,
        UploadedFile $archivo,
        string $documentoPersona,
        string $nombreBase,
        array &$rutasNuevas,
        array &$rutasAnteriores
    ): array {
        $resultadoCarga = $this->documentalStorage->guardarArchivo(
            archivo: $archivo,
            documento: $documentoPersona,
            categoria: 'PERMISO_PERMANENTE',
            radicado: $idNovedad,
            nombreBase: $nombreBase,
            year: (int) Carbon::now()->format('Y')
        );

        $rutaNueva = trim((string) ($resultadoCarga['relative_path'] ?? ''));
        if ($rutaNueva === '') {
            throw new RuntimeException('No se genero la ruta relativa de un documento del permiso permanente.');
        }
        $rutasNuevas[] = $rutaNueva;

        $registro = DB::connection(self::CONNECTION)
            ->table('EMP_NOVEDADES_DOCUMENTOS')
            ->where('id_novedad', $idNovedad)
            ->where('id_tipo_documento', $idTipoDocumento)
            ->first(['id', 'ruta_documento']);

        if ($registro) {
            $rutaAnterior = trim((string) ($registro->ruta_documento ?? ''));
            if ($rutaAnterior !== '') {
                $rutasAnteriores[] = $rutaAnterior;
            }

            DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_DOCUMENTOS')
                ->where('id', (string) $registro->id)
                ->update([
                    'ruta_documento' => $rutaNueva,
                    'fecha_creacion' => now(),
                ]);

            $idDocumento = (string) $registro->id;
        } else {
            $idDocumento = (string) Str::uuid();
            DB::connection(self::CONNECTION)
                ->table('EMP_NOVEDADES_DOCUMENTOS')
                ->insert([
                    'id' => $idDocumento,
                    'id_novedad' => $idNovedad,
                    'id_tipo_documento' => $idTipoDocumento,
                    'ruta_documento' => $rutaNueva,
                    'fecha_creacion' => now(),
                ]);
        }

        return [
            'id' => $idDocumento,
            'id_novedad' => $idNovedad,
            'id_tipo_documento' => $idTipoDocumento,
            'ruta_documento' => $rutaNueva,
        ];
    }

    private function construirNombreAdjuntoCorreo(string $tipoDocumento, ?string $identificacion, ?string $extension = null): string
    {
        $baseNombre = trim($tipoDocumento) !== '' ? trim($tipoDocumento) : 'adjunto_permiso_permanente';
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
            $baseNombre = 'adjunto_permiso_permanente';
        }

        $extension = strtolower(trim((string) $extension));

        return $extension !== '' ? $baseNombre.'.'.$extension : $baseNombre;
    }
}
