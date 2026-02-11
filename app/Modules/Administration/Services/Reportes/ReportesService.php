<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Modules\Administration\Models\FirmaPoliticas;
use App\Modules\Administration\Models\Reporteador;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReportesService
{
    private const CACHE_ACTIVOS_KEY = 'reportes_activos_por_area';

    private const CACHE_ACTIVOS_TTL_MINUTES = 10;

    private const PERMISO_PREFIX = 'administracion.reportes.id_';

    /** @var ApiReportes */
    protected $apiReportes;

    /** @var Collection|null Cache en memoria de reportes activos agrupados por area */
    protected ?Collection $reportesActivosPorArea = null;

    public function __construct(ApiReportes $apiReportes)
    {
        $this->apiReportes = $apiReportes;
    }

    /**
     * Solicita datos de un reporte via API y aplica formato especial.
     */
    public function obtenerDatosReporte(array $params)
    {
        // Normalizamos el id para la API
        if (isset($params['id'])) {
            $params['idReporte'] = $params['id'];
            unset($params['id']);
        }

        $this->validarParametrosObligatorios($params);

        $reporte = Reporteador::findOrFail($params['idReporte']);
        $inicio = microtime(true);

        // Consultar API
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
     * Devuelve los reportes activos agrupados por area (cache liviana).
     */
    protected function obtenerReportesActivosAgrupados(): Collection
    {
        if ($this->reportesActivosPorArea === null) {
            $this->reportesActivosPorArea = Cache::remember(
                self::CACHE_ACTIVOS_KEY,
                now()->addMinutes(self::CACHE_ACTIVOS_TTL_MINUTES),
                function () {
                    return Reporteador::get()
                        ->groupBy('area');
                }
            );
        }

        return $this->reportesActivosPorArea;
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
        foreach ($this->obtenerAreasConfig() as $slug => $config) {
            if (($config['nombre_bd'] ?? null) === $areaNombre) {
                return [
                    'slug' => $slug,
                    'ruta' => route($config['ruta'] ?? 'reportes.area', ['area' => $slug]),
                ];
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
        $reportesPorArea = $this->obtenerReportesActivosAgrupados();

        foreach ($areasConfig as $slug => $conf) {

            $areaNombre = $conf['nombre_bd'] ?? null;
            $permisoArea = $conf['permiso'] ?? null;

            if (! $areaNombre) {
                continue;
            }

            $accesoPorArea = $permisoArea ? $user->can($permisoArea) : false;

            $reportesArea = $reportesPorArea->get($areaNombre, collect());

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
        $reportes = $this->obtenerReportesActivosAgrupados()
            ->get($config['nombre_bd'] ?? $slugArea, collect());

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
        $reportesArea = $this->obtenerReportesActivosAgrupados()
            ->get($config['nombre_bd'], collect());

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
