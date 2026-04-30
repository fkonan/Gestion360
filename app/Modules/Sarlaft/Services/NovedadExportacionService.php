<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\NovedadExportacion;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Pagination\LengthAwarePaginator;

class NovedadExportacionService
{
    public function registrarNovedadVinculante(
        RegistroLista $registro,
        string $tipoNovedad,
        string $nombreLista,
        ?int $sincronizacionLogId = null,
    ): void {
        NovedadExportacion::query()->create([
            'origen_lista' => 'vinculante',
            'tipo_novedad' => $tipoNovedad,
            'registro_lista_id' => (int) $registro->id,
            'lista_negra_id' => null,
            'sincronizacion_log_id' => $sincronizacionLogId,
            'lista_id' => (int) $registro->lista_id,
            'nombre_lista' => $nombreLista,
            'datos' => [
                'tipo_entidad' => $registro->tipo_entidad,
                'tipo_identificacion' => $registro->tipo_identificacion,
                'identificacion' => $registro->identificacion,
                'nombres' => $registro->nombres,
                'alias' => $registro->alias,
                'pais' => $registro->pais,
                'motivo' => $registro->motivo,
                'fecha_inclusion' => $registro->fecha_inclusion?->format('Y-m-d'),
                'referencia_externa' => $registro->referencia_externa,
                'estado' => $registro->estado,
            ],
            'created_at' => now(),
        ]);
    }

    public function registrarNovedadInterna(
        ListaNegraInterna $registro,
        string $tipoNovedad,
    ): void {
        NovedadExportacion::query()->create([
            'origen_lista' => 'interna',
            'tipo_novedad' => $tipoNovedad,
            'registro_lista_id' => null,
            'lista_negra_id' => (int) $registro->id,
            'sincronizacion_log_id' => null,
            'lista_id' => null,
            'nombre_lista' => 'Lista Restrictiva Interna',
            'datos' => [
                'tipo_entidad' => $registro->tipo_entidad,
                'tipo_documento' => $registro->tipo_documento,
                'numero_documento' => $registro->numero_documento,
                'nombres' => $registro->nombres,
                'motivo' => $registro->motivo,
                'estado' => $registro->estado,
                'retirado_at' => $registro->retirado_at?->toIso8601String(),
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{novedades: LengthAwarePaginator, total: int, conteo_por_tipo: array{ingresos: int, salidas: int, actualizados: int}}
     */
    public function obtenerNovedades(string $fechaDesde, int $page = 1): array
    {
        $hasta = now()->toDateString().' 23:59:59';

        $consulta = NovedadExportacion::query()
            ->whereBetween('created_at', [$fechaDesde.' 00:00:00', $hasta])
            ->orderBy('created_at')
            ->orderBy('id');

        $conteoPorTipo = [
            'ingresos' => (clone $consulta)->where('tipo_novedad', 'ingreso')->count(),
            'salidas' => (clone $consulta)->where('tipo_novedad', 'salida')->count(),
            'actualizados' => (clone $consulta)->where('tipo_novedad', 'actualizado')->count(),
        ];

        $paginador = $consulta->paginate(1000, ['*'], 'page', $page);

        return [
            'novedades' => $paginador,
            'total' => $paginador->total(),
            'conteo_por_tipo' => $conteoPorTipo,
        ];
    }
}
