<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Modules\Administration\Models\FirmaPoliticas;
use App\Modules\Administration\Models\Reporteador;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReportesService
{
    private const PERMISO_PREFIX = 'administracion.reportes.id_';

    /** @var ApiReportes */
    protected $apiReportes;

    /** @var Collection|null Cache en memoria de reportes activos */
    protected ?Collection $reportesActivos = null;

    public function __construct(ApiReportes $apiReportes)
    {
        $this->apiReportes = $apiReportes;
    }

    /**
     * Solicita datos de un reporte local y aplica formato especial.
     */
    public function obtenerDatosReporte(array $params)
    {
        // Normalizamos el id para el servicio de reportes
        if (isset($params['id'])) {
            $params['idReporte'] = $params['id'];
            unset($params['id']);
        }

        $this->validarParametrosObligatorios($params);

        $reporte = Reporteador::findOrFail($params['idReporte']);
        $inicio = microtime(true);

        // Consultar servicio de reportes
        $data = $this->apiReportes->obtenerReporte($params);

        if (! $data) {
            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/reportes/apiReportes.log'),
                'days' => 7,
            ])->info('Reporte sin datos o error al obtener', [
                'id_reporte' => $reporte->id,
                'area' => $reporte->area,
                'user_id' => Auth::id(),
                'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
            ]);

            return [];
        }

        // Incrementar contador de consultas sin bloquear la respuesta
        try {
            $reporte->increment('total_consultas');
        } catch (\Throwable $e) {
            // Silenciar si falla el write; no debe afectar al usuario
        }

        // Formatos especiales por reporte
        $data = $this->formatoEspecialReporte($reporte->id, $data);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/reportes/apiReportes.log'),
            'days' => 7,
        ])->info('Reporte consultado', [
            'id_reporte' => $reporte->id,
            'area' => $reporte->area,
            'user_id' => Auth::id(),
            'rows' => is_array($data) ? count($data) : 0,
            'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        return $data;
    }

    /**
     * Solicita una vista previa limitada para reportes muy grandes.
     */
    public function obtenerDatosReporteLimitado(array $params, int $limit): array
    {
        if (isset($params['id'])) {
            $params['idReporte'] = $params['id'];
            unset($params['id']);
        }

        $this->validarParametrosObligatorios($params);

        $reporte = Reporteador::findOrFail($params['idReporte']);
        $inicio = microtime(true);

        $data = $this->apiReportes->obtenerReporteLimitado($params, $limit);

        if (! $data) {
            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/reportes/apiReportes.log'),
                'days' => 7,
            ])->info('Reporte limitado sin datos o error al obtener', [
                'id_reporte' => $reporte->id,
                'area' => $reporte->area,
                'user_id' => Auth::id(),
                'limit' => $limit,
                'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
            ]);

            return [];
        }

        $data = $this->formatoEspecialReporte($reporte->id, $data);

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/reportes/apiReportes.log'),
            'days' => 7,
        ])->info('Reporte consultado (limitado)', [
            'id_reporte' => $reporte->id,
            'area' => $reporte->area,
            'user_id' => Auth::id(),
            'rows' => is_array($data) ? count($data) : 0,
            'limit' => $limit,
            'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        return $data;
    }

    /**
     * Retorna un iterable de filas para exportacion de archivos grandes.
     */
    public function obtenerCursorReporte(array $params)
    {
        if (isset($params['id'])) {
            $params['idReporte'] = $params['id'];
            unset($params['id']);
        }

        $this->validarParametrosObligatorios($params);

        return $this->apiReportes->obtenerReporteCursor($params);
    }

    /**
     * Solicita datos paginados de un reporte local.
     */
    public function obtenerDatosReportePaginado(array $params, int $limit, int $offset): array
    {
        if (isset($params['id'])) {
            $params['idReporte'] = $params['id'];
            unset($params['id']);
        }

        $this->validarParametrosObligatorios($params);

        $reporte = Reporteador::findOrFail($params['idReporte']);
        $inicio = microtime(true);

        $resultado = $this->apiReportes->obtenerReportePaginado($params, $limit, $offset);

        if (! $resultado || ! is_array($resultado)) {
            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/reportes/apiReportes.log'),
                'days' => 7,
            ])->info('Reporte paginado sin datos o error al obtener', [
                'id_reporte' => $reporte->id,
                'area' => $reporte->area,
                'user_id' => Auth::id(),
                'limit' => $limit,
                'offset' => $offset,
                'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
            ]);

            return ['total' => 0, 'rows' => []];
        }

        $rows = is_array($resultado['rows'] ?? null) ? $resultado['rows'] : [];
        $total = (int) ($resultado['total'] ?? count($rows));

        // Formatos especiales por reporte (aplicados a la pagina actual)
        $rows = $this->formatoEspecialReporte($reporte->id, $rows);

        if ($total < count($rows)) {
            $total = count($rows);
        }

        try {
            $reporte->increment('total_consultas');
        } catch (\Throwable $e) {
            // Silenciar si falla el write; no debe afectar al usuario
        }

        Log::build([
            'driver' => 'daily',
            'path' => storage_path('logs/reportes/apiReportes.log'),
            'days' => 7,
        ])->info('Reporte consultado (paginado)', [
            'id_reporte' => $reporte->id,
            'area' => $reporte->area,
            'user_id' => Auth::id(),
            'rows' => count($rows),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'duration_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        return [
            'total' => $total,
            'rows' => $rows,
        ];
    }

    /**
     * Ajusta formatos o filtros puntuales por id de reporte.
     */
    public function formatoEspecialReporte(int $idReporte, array $data)
    {
        switch ($idReporte) {
            // Reporte 19: Conductores y empleados sin firma politicas
            /*
              Este reporte muestra los conductores y empleados que no tienen firma en las politicas.
              Por lo que se requiere cargar desde la base de datos de gestion de pasajes cuales son los empleados que tienen firma.
            */
            case 19:
                $empleadosConFirma = FirmaPoliticas::select('DocCon')
                    ->where('FirFecReg', '>=', '2025-10-01') // Fecha desde la cual se consideran las firmas
                    ->distinct()
                    ->pluck('DocCon')
                    ->toArray();

                // Filtrar los datos para excluir los empleados que tienen firma
                $data = array_values(array_filter($data, function ($item) use ($empleadosConFirma) {
                    return ! in_array($item['IDENTIFICACION'], $empleadosConFirma);
                }));

                return $data;
            default:
                return $data;
        }
    }

    protected function obtenerAreasConfig(): array
    {
        return config('reporteador.areas', []);
    }

    protected function validarParametrosObligatorios(array $params): void
    {
        if (empty($params['idReporte'])) {
            throw new \InvalidArgumentException('El id del reporte es obligatorio.');
        }
    }

    /**
     * Devuelve los reportes activos (cache liviana).
     */
    protected function obtenerReportesActivos(): Collection
    {
        if ($this->reportesActivos === null) {
            // Sin cache persistente para reflejar cambios manuales en BD al instante.
            $this->reportesActivos = Reporteador::get();
        }

        return $this->reportesActivos;
    }

    protected function normalizarArea(?string $area): string
    {
        return strtolower(trim((string) $area));
    }

    /**
     * Soporta area simple (ej: RRHH) y lista delimitada (ej: |RRHH|Unidad pasajes|).
     */
    protected function extraerAreasDesdeCampo(?string $areaRaw): array
    {
        if ($areaRaw === null) {
            return [];
        }

        $areaRaw = trim($areaRaw);

        if ($areaRaw === '') {
            return [];
        }

        // Compatibilidad adicional por si se guarda JSON en pruebas.
        if (str_starts_with($areaRaw, '[')) {
            $decoded = json_decode($areaRaw, true);
            if (is_array($decoded)) {
                return collect($decoded)
                    ->filter(fn ($area) => is_string($area) && trim($area) !== '')
                    ->map(fn ($area) => trim($area))
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        if (str_contains($areaRaw, '|')) {
            return collect(explode('|', $areaRaw))
                ->map(fn ($area) => trim($area))
                ->filter(fn ($area) => $area !== '')
                ->unique()
                ->values()
                ->all();
        }

        return [$areaRaw];
    }

    protected function reportePerteneceAArea(Reporteador $reporte, string $areaNombre): bool
    {
        $areaBuscada = $this->normalizarArea($areaNombre);
        $areasReporte = collect($this->extraerAreasDesdeCampo($reporte->area ?? null))
            ->map(fn ($area) => $this->normalizarArea($area))
            ->values()
            ->all();

        return in_array($areaBuscada, $areasReporte, true);
    }

    protected function filtrarReportesPorNombreArea(Collection $reportes, string $areaNombre): Collection
    {
        return $reportes->filter(fn ($reporte) => $this->reportePerteneceAArea($reporte, $areaNombre));
    }

    protected function obtenerConfigPorSlug(string $slug): ?array
    {
        $areas = $this->obtenerAreasConfig();

        return $areas[$slug] ?? null;
    }

    /**
     * Nombre del permiso individual para un reporte especifico.
     */
    protected function permisoReporteId(int $id): string
    {
        return self::PERMISO_PREFIX.$id;
    }

    /**
     * Slug y ruta generada a partir del nombre de area guardado en BD.
     */
    public function obtenerRutaAreaPorNombre(string $areaNombre): ?array
    {
        $areasReporte = $this->extraerAreasDesdeCampo($areaNombre);

        foreach ($areasReporte as $area) {
            foreach ($this->obtenerAreasConfig() as $slug => $config) {
                if ($this->normalizarArea($config['nombre_bd'] ?? null) === $this->normalizarArea($area)) {
                    return [
                        'slug' => $slug,
                        'ruta' => route($config['ruta'] ?? 'reportes.area', ['area' => $slug]),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Devuelve listado de tipos de reporte accesibles por el usuario autenticado.
     */
    public function tiposReporte()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $result = [];

        $areasConfig = $this->obtenerAreasConfig();
        $reportesActivos = $this->obtenerReportesActivos();

        foreach ($areasConfig as $slug => $conf) {

            $areaNombre = $conf['nombre_bd'] ?? null;
            $permisoArea = $conf['permiso'] ?? null;

            if (! $areaNombre) {
                continue;
            }

            $accesoPorArea = $permisoArea ? $user->can($permisoArea) : false;

            $reportesArea = $this->filtrarReportesPorNombreArea($reportesActivos, $areaNombre);

            $accesoIndividual = $reportesArea->contains(
                fn ($rep) => $user->can($this->permisoReporteId($rep->id))
            );

            if (! $accesoPorArea && ! $accesoIndividual) {
                continue;
            }

            $result[] = [
                'titulo' => $conf['titulo'] ?? $areaNombre,
                'descripcion' => $conf['descripcion'] ?? 'Consultar',
                'tooltip' => $conf['tooltip'] ?? null,
                'ruta' => [$conf['ruta'] ?? 'reportes.area', $slug],
                'icono' => $conf['icono'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Verifica si el usuario puede acceder a un area por permiso general o individual.
     */
    public function usuarioPuedeVerArea($slugArea, $permisoArea)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $config = $this->obtenerConfigPorSlug($slugArea);

        if (! $config) {
            return false;
        }

        $permisoConfig = $config['permiso'] ?? $permisoArea;

        // Permiso de area = acceso completo
        if ($permisoConfig && $user->can($permisoConfig)) {
            return true;
        }

        // Buscar reportes de esta area
        $reportes = $this->filtrarReportesPorNombreArea(
            $this->obtenerReportesActivos(),
            $config['nombre_bd'] ?? $slugArea
        );

        // Ver si el usuario tiene permiso individual a alguno
        foreach ($reportes as $r) {
            if ($user->can($this->permisoReporteId($r->id))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene reportes de un area aplicando permisos de area e individuales.
     */
    public function obtenerReportesPorArea(string $area): array
    {
        $config = $this->obtenerConfigPorSlug($area);

        if (! $config) {
            abort(404, 'Area no encontrada');
        }
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. OBTENER TODOS LOS REPORTES DEL AREA
        $reportesArea = $this->filtrarReportesPorNombreArea(
            $this->obtenerReportesActivos(),
            $config['nombre_bd']
        );

        // 2. FILTRAR QUE REPORTES INDIVIDUALES EL USUARIO PUEDE VER
        $reportesConPermisoIndividual = $reportesArea->filter(function ($reporte) use ($user) {
            return $user->can($this->permisoReporteId($reporte->id));
        });

        // 3. SI EL USUARIO TIENE PERMISO DE AREA -> MOSTRAR TODOS
        $permisoArea = $config['permiso'] ?? null;
        if ($permisoArea && $user->can($permisoArea)) {
            return ['reportes' => $reportesArea->values()];
        }

        // 4. SIN PERMISO DE AREA, PERO CON ALGUN PERMISO INDIVIDUAL -> MOSTRAR SOLO ESOS
        if ($reportesConPermisoIndividual->isNotEmpty()) {
            return ['reportes' => $reportesConPermisoIndividual->values()];
        }

        // 5. SIN PERMISO DE AREA NI INDIVIDUAL -> BLOQUEAR
        throw new AuthorizationException('No tienes permiso para acceder a estos reportes.');
    }
}
