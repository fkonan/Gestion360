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
                'alertas' => static function (HasMany $relation): void {
                    $relation->latest('created_at')
                        ->with(['intento.sistema']);
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
            ->get(['id', 'sistema_origen', 'presta_servicio', 'contexto_operacion']);

        return [
            'total_operaciones' => $registros->count(),
            'total_permitidas' => $registros->where('presta_servicio', true)->count(),
            'total_bloqueadas' => $registros->where('presta_servicio', false)->count(),
            'total_pasajes' => $registros
                ->filter(fn (Consulta $consulta): bool => $this->resolverOperacionConsulta($consulta) === 'pasaje')
                ->count(),
            'total_remesas' => $registros
                ->filter(fn (Consulta $consulta): bool => $this->esOperacionRemesa($this->resolverOperacionConsulta($consulta)))
                ->count(),
            'con_alerta' => $this->construirConsultaBase($filtros)->has('alertas')->count(),
        ];
    }

    /**
     * @param  array<string, string|null>  $filtros
     */
    private function construirConsultaBase(array $filtros): Builder
    {
        $query = Consulta::query();

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
            $this->aplicarFiltroOperacion($query, 'pasaje');
        }

        if ($filtros['operacion'] === 'remesa') {
            $this->aplicarFiltroOperacion($query, 'remesa');
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

    private function aplicarFiltroOperacion(Builder $query, string $operacion): void
    {
        if ($operacion === 'pasaje') {
            $query->where(function (Builder $operacionQuery): void {
                $operacionQuery
                    ->where('sistema_origen', 'simulacion_pasaje')
                    ->orWhere('contexto_operacion->tipo_operacion', 'pasaje');
            });

            return;
        }

        if ($operacion === 'remesa') {
            $query->where(function (Builder $operacionQuery): void {
                $operacionQuery
                    ->where('sistema_origen', 'simulacion_remesa')
                    ->orWhereIn('contexto_operacion->tipo_operacion', ['remesa', 'pago']);
            });
        }
    }

    private function resolverOperacionConsulta(Consulta $consulta): ?string
    {
        $contexto = is_array($consulta->contexto_operacion) ? $consulta->contexto_operacion : [];
        $tipoOperacion = isset($contexto['tipo_operacion']) && is_string($contexto['tipo_operacion'])
            ? strtolower(trim($contexto['tipo_operacion']))
            : null;

        if ($tipoOperacion !== null && $tipoOperacion !== '') {
            return $tipoOperacion;
        }

        if ($consulta->sistema_origen === 'simulacion_pasaje') {
            return 'pasaje';
        }

        if ($consulta->sistema_origen === 'simulacion_remesa') {
            return 'remesa';
        }

        $sistemaOrigen = strtolower((string) $consulta->sistema_origen);

        if (str_contains($sistemaOrigen, 'pasaje')) {
            return 'pasaje';
        }

        if (str_contains($sistemaOrigen, 'remesa')) {
            return 'remesa';
        }

        return null;
    }

    private function esOperacionRemesa(?string $operacion): bool
    {
        return in_array($operacion, ['remesa', 'pago'], true);
    }
}
