<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\NovedadExportacion;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Support\Collection;

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
     * @return array{novedades: Collection<int, NovedadExportacion>, siguiente_punto_de_control:int, hay_mas:bool}
     */
    public function obtenerNovedades(int $puntoDeControl, int $tamanoLote, ?int $listaId = null): array
    {
        $consulta = NovedadExportacion::query()
            ->where('id', '>', $puntoDeControl)
            ->orderBy('id');

        if ($listaId !== null) {
            $consulta->where('lista_id', $listaId);
        }

        $novedades = $consulta
            ->limit($tamanoLote + 1)
            ->get();

        $hayMas = $novedades->count() > $tamanoLote;

        if ($hayMas) {
            $novedades = $novedades->take($tamanoLote);
        }

        $siguientePuntoDeControl = $novedades->last()?->id ?? $puntoDeControl;

        return [
            'novedades' => $novedades,
            'siguiente_punto_de_control' => (int) $siguientePuntoDeControl,
            'hay_mas' => $hayMas,
        ];
    }

    public function obtenerUltimoPuntoDeControl(): int
    {
        return (int) (NovedadExportacion::query()->max('id') ?? 0);
    }
}
