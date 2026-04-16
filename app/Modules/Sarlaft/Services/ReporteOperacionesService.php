<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Consulta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporteOperacionesService
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return array{
     *   filtros: array<string, string|null>,
     *   stats: array<string, int>,
     *   operaciones: LengthAwarePaginator<int, Consulta>
     * }
     */
    public function construirReporte(array $filtros): array
    {
        $filtrosNormalizados = $this->normalizarFiltros($filtros);
        $query = $this->construirConsultaBase($filtrosNormalizados);

        /** @var LengthAwarePaginator<int, Consulta> $operaciones */
        $operaciones = $query
            ->with([
                'simulacionPasaje',
                'simulacionRemesa',
                'alertas' => static function (HasMany $relation): void {
                    $relation->latest('created_at');
                },
            ])
            ->withCount('alertas')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return [
            'filtros' => $filtrosNormalizados,
            'stats' => $this->construirEstadisticas($filtrosNormalizados),
            'operaciones' => $operaciones,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, string|null>
     */
    public function normalizarFiltros(array $filtros): array
    {
        $tipoDocumento = isset($filtros['tipo_documento']) ? strtoupper(trim((string) $filtros['tipo_documento'])) : null;
        $numeroDocumento = isset($filtros['numero_documento']) ? trim((string) $filtros['numero_documento']) : null;
        $nombre = isset($filtros['nombre']) ? trim((string) $filtros['nombre']) : null;
        $operacion = isset($filtros['operacion']) ? trim((string) $filtros['operacion']) : null;
        $resultado = isset($filtros['resultado']) ? trim((string) $filtros['resultado']) : null;
        $fechaDesde = isset($filtros['fecha_desde']) ? trim((string) $filtros['fecha_desde']) : null;
        $fechaHasta = isset($filtros['fecha_hasta']) ? trim((string) $filtros['fecha_hasta']) : null;

        return [
            'tipo_documento' => $tipoDocumento !== '' ? $tipoDocumento : null,
            'numero_documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
            'nombre' => $nombre !== '' ? $nombre : null,
            'operacion' => in_array($operacion, ['pasaje', 'remesa'], true) ? $operacion : null,
            'resultado' => in_array($resultado, ['permitida', 'bloqueada'], true) ? $resultado : null,
            'fecha_desde' => $fechaDesde !== '' ? $fechaDesde : null,
            'fecha_hasta' => $fechaHasta !== '' ? $fechaHasta : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function construirEstadisticas(array $filtros): array
    {
        $registros = $this->construirConsultaBase($filtros)
            ->get(['id', 'sistema_origen', 'presta_servicio']);

        return [
            'total_operaciones' => $registros->count(),
            'total_permitidas' => $registros->where('presta_servicio', true)->count(),
            'total_bloqueadas' => $registros->where('presta_servicio', false)->count(),
            'total_pasajes' => $registros->where('sistema_origen', 'simulacion_pasaje')->count(),
            'total_remesas' => $registros->where('sistema_origen', 'simulacion_remesa')->count(),
            'con_alerta' => $this->construirConsultaBase($filtros)->has('alertas')->count(),
        ];
    }

    /**
     * @param  array<string, string|null>  $filtros
     */
    private function construirConsultaBase(array $filtros): Builder
    {
        $query = Consulta::query()
            ->whereIn('sistema_origen', ['simulacion_pasaje', 'simulacion_remesa']);

        if ($filtros['tipo_documento'] !== null) {
            $query->where('tipo_documento', $filtros['tipo_documento']);
        }

        if ($filtros['numero_documento'] !== null) {
            $query->where('numero_documento', $filtros['numero_documento']);
        }

        if ($filtros['nombre'] !== null) {
            $query->where('nombre_consultado', 'like', '%'.$filtros['nombre'].'%');
        }

        if ($filtros['operacion'] === 'pasaje') {
            $query->where('sistema_origen', 'simulacion_pasaje');
        }

        if ($filtros['operacion'] === 'remesa') {
            $query->where('sistema_origen', 'simulacion_remesa');
        }

        if ($filtros['resultado'] === 'permitida') {
            $query->where('presta_servicio', true);
        }

        if ($filtros['resultado'] === 'bloqueada') {
            $query->where('presta_servicio', false);
        }

        if ($filtros['fecha_desde'] !== null) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if ($filtros['fecha_hasta'] !== null) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query;
    }
}
