<?php

namespace App\Modules\SIG\Services;

use App\Modules\SIG\Models\DocumentosVersiones;
use App\Modules\SIG\Models\Ubicaciones;
use Illuminate\Support\Collection;

class EmisionesService
{
    public function obtenerDatosFormularioNuevaEmision(int $documentoId): array
    {
        $ubicacionesElabora = $this->obtenerUbicacionesPorTipo('E');
        $ubicacionesRevisa = $this->obtenerUbicacionesPorTipo('R');
        $ubicacionesAprueba = $this->obtenerUbicacionesPorTipo('A');

        $proximaVersion = DocumentosVersiones::where('documento_id', $documentoId)
            ->whereNotNull('version')
            ->max('version');
        $proximaVersion = $proximaVersion ? ($proximaVersion + 1) : 1;

        $ultimaAprobada = DocumentosVersiones::where('documento_id', $documentoId)
            ->where('estado', 'APROBADO')
            ->orderByDesc('version')
            ->first();

        return [
            'ubicacionesElabora' => $ubicacionesElabora,
            'ubicacionesRevisa' => $ubicacionesRevisa,
            'ubicacionesAprueba' => $ubicacionesAprueba,
            'proximaVersion' => $proximaVersion,
            'ultimaAprobada' => $ultimaAprobada,
        ];
    }

    public function obtenerUbicacionesFormulario(): array
    {
        return [
            'ubicacionesElabora' => $this->obtenerUbicacionesPorTipo('E'),
            'ubicacionesRevisa' => $this->obtenerUbicacionesPorTipo('R'),
            'ubicacionesAprueba' => $this->obtenerUbicacionesPorTipo('A'),
        ];
    }

    public function obtenerEmisionesDocumento(int $documentoId): Collection
    {
        return DocumentosVersiones::where('documento_id', $documentoId)
            ->whereIn('estado', ['APROBADO', 'HISTORICO'])
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get([
                'id',
                'version',
                'comentario_revision',
                'id_elabora',
                'id_revisa',
                'id_aprueba',
                'fecha_aprobacion',
                'estado',
                'fecha_elaboracion',
            ]);
    }

    public function obtenerUbicacionesEmisiones(Collection $versiones): Collection
    {
        $ids = $versiones->pluck('id_elabora')
            ->merge($versiones->pluck('id_revisa'))
            ->merge($versiones->pluck('id_aprueba'))
            ->filter()
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->all();

        return $ids
          ? Ubicaciones::whereIn('id', $ids)->get(['id', 'nombre'])->keyBy('id')
          : collect();
    }

    public function mapearEmisionesConNombres(Collection $versiones, Collection $ubicaciones): Collection
    {
        return $versiones->map(function ($version) use ($ubicaciones) {
            $version->elaboro_nombre = optional($ubicaciones->get(trim((string) $version->id_elabora)))->nombre;
            $version->reviso_nombre = optional($ubicaciones->get(trim((string) $version->id_revisa)))->nombre;
            $version->aprueba_nombre = optional($ubicaciones->get(trim((string) $version->id_aprueba)))->nombre;

            return $version;
        });
    }

    public function obtenerEmisionesPendientes(): Collection
    {
        $ultimosIds = DocumentosVersiones::orderByDesc('id')
            ->get(['id', 'documento_id', 'estado'])
            ->unique('documento_id')
            ->filter(fn ($v) => $v->estado === 'EN_REVISION')
            ->pluck('id');

        return DocumentosVersiones::with([
            'documento' => function ($query) {
                $query->select('id', 'codigo', 'nombre');
            },
        ])
            ->whereIn('id', $ultimosIds)
            ->orderByDesc('fecha_elaboracion')
            ->get([
                'id',
                'documento_id',
                'version',
                'comentario_revision',
                'archivo_url',
                'paginas',
                'id_elabora',
                'id_revisa',
                'id_aprueba',
                'fecha_elaboracion',
                'estado',
            ]);
    }

    public function mapearPendientes(Collection $versiones, Collection $ubicaciones): Collection
    {
        return $versiones->map(function ($version) use ($ubicaciones) {
            $obtenerNombre = function ($id) use ($ubicaciones) {
                $id = trim((string) $id);
                if ($id === '') {
                    return null;
                }

                return optional($ubicaciones->get($id))->nombre;
            };

            return [
                'id' => $version->id,
                'documento_id' => $version->documento_id,
                'codigo' => $version->documento?->codigo ?? 'SIN CODIGO',
                'nombre' => $version->documento?->nombre,
                'version' => $version->version,
                'comentario_revision' => $version->comentario_revision,
                'archivo_url' => $version->archivo_url,
                'paginas' => $version->paginas,
                'elaboro' => $obtenerNombre($version->id_elabora),
                'reviso' => $obtenerNombre($version->id_revisa),
                'aprueba' => $obtenerNombre($version->id_aprueba),
                'fecha_elaboracion' => $version->fecha_elaboracion,
                'estado' => $version->estado,
                'tipo_solicitud' => $version->documento?->codigo ? 'EMISION' : 'NUEVO_DOCUMENTO',
            ];
        })->values();
    }

    public function obtenerPendienteRevision(int $versionId): ?array
    {
        $version = DocumentosVersiones::with([
            'documento' => function ($query) {
                $query->select('id', 'codigo', 'nombre');
            },
        ])->find($versionId, [
            'id',
            'documento_id',
            'version',
            'comentario_revision',
            'archivo_url',
            'paginas',
            'id_elabora',
            'id_revisa',
            'id_aprueba',
            'fecha_elaboracion',
            'estado',
        ]);

        if (! $version) {
            return null;
        }

        $ultimaVersion = DocumentosVersiones::where('documento_id', $version->documento_id)
            ->orderByDesc('id')
            ->first(['id', 'estado']);

        if (! $ultimaVersion || $ultimaVersion->id !== $version->id || $ultimaVersion->estado !== 'EN_REVISION') {
            return null;
        }

        $ubicaciones = $this->obtenerUbicacionesEmisiones(collect([$version]));
        $vistaPrevia = $this->resolverVistaPreviaArchivo($version->archivo_url);

        $obtenerNombre = function ($id) use ($ubicaciones) {
            $id = trim((string) $id);
            if ($id === '') {
                return null;
            }

            return optional($ubicaciones->get($id))->nombre;
        };

        return [
            'id' => $version->id,
            'documento_id' => $version->documento_id,
            'codigo' => $version->documento?->codigo ?? 'SIN CODIGO',
            'nombre' => $version->documento?->nombre,
            'version' => $version->version,
            'comentario_revision' => $version->comentario_revision,
            'archivo_url' => $version->archivo_url,
            'archivo_preview_url' => $vistaPrevia['url'],
            'archivo_preview_demo' => $vistaPrevia['es_demo'],
            'archivo_preview_mensaje' => $vistaPrevia['mensaje'],
            'paginas' => $version->paginas,
            'id_elabora' => $version->id_elabora,
            'id_revisa' => $version->id_revisa,
            'id_aprueba' => $version->id_aprueba,
            'elaboro' => $obtenerNombre($version->id_elabora),
            'reviso' => $obtenerNombre($version->id_revisa),
            'aprueba' => $obtenerNombre($version->id_aprueba),
            'fecha_elaboracion' => $version->fecha_elaboracion,
            'estado' => $version->estado,
            'tipo_solicitud' => $version->documento?->codigo ? 'EMISION' : 'NUEVO_DOCUMENTO',
        ];
    }

    private function obtenerUbicacionesPorTipo(string $tipo): Collection
    {
        return Ubicaciones::select('id', 'nombre')
            ->where('tipo', $tipo)
            ->orderBy('nombre')
            ->get();
    }

    private function resolverVistaPreviaArchivo(?string $archivoUrl): array
    {
        $fallback = [
            'url' => asset('storage/pdfs/documento_pruebas.pdf'),
            'es_demo' => true,
            'mensaje' => 'Se muestra el PDF de prueba del modulo porque esta solicitud aun no tiene un archivo PDF disponible para visualizar.',
        ];

        $archivoUrl = trim((string) $archivoUrl);
        if ($archivoUrl === '') {
            return $fallback;
        }

        $rutaAnalisis = parse_url($archivoUrl, PHP_URL_PATH) ?: $archivoUrl;
        $extension = strtolower(pathinfo($rutaAnalisis, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return $fallback;
        }

        if (filter_var($archivoUrl, FILTER_VALIDATE_URL)) {
            return [
                'url' => $archivoUrl,
                'es_demo' => false,
                'mensaje' => 'Vista previa del archivo asociado a la solicitud.',
            ];
        }

        $archivoRelativo = str_replace('\\', '/', ltrim($archivoUrl, '/'));
        $archivoRelativo = preg_replace('#^public/#', '', $archivoRelativo);

        $candidatos = array_values(array_unique(array_filter([
            $archivoRelativo,
            str_starts_with($archivoRelativo, 'storage/') ? $archivoRelativo : 'storage/'.$archivoRelativo,
            str_starts_with($archivoRelativo, 'pdfs/') ? 'storage/'.$archivoRelativo : null,
            'storage/pdfs/'.basename($archivoRelativo),
        ])));

        foreach ($candidatos as $candidato) {
            if (is_file(public_path($candidato))) {
                return [
                    'url' => asset($candidato),
                    'es_demo' => false,
                    'mensaje' => 'Vista previa del archivo asociado a la solicitud.',
                ];
            }
        }

        return $fallback;
    }
}
