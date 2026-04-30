<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Api\ExportarListasRequest;
use App\Modules\Sarlaft\Http\Resources\ListaNegraInternaResource;
use App\Modules\Sarlaft\Http\Resources\RegistroListaResource;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Services\NovedadExportacionService;
use Illuminate\Http\JsonResponse;

class ListaRegistroController extends Controller
{
    public function __construct(
        private readonly NovedadExportacionService $novedadExportacionService,
    ) {}

    /**
     * Exportacion de listas vinculantes e internas.
     *
     * - tipo_descarga=completa: entrega catalogo completo activo.
     * - tipo_descarga=novedades: entrega delta desde punto_de_control.
     */
    public function index(ExportarListasRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $tipoDescarga = (string) $datos['tipo_descarga'];
        $listaId = isset($datos['lista_id']) ? (int) $datos['lista_id'] : null;

        if ($tipoDescarga === 'novedades') {
            return $this->respuestaNovedades(
                puntoDeControl: (int) ($datos['punto_de_control'] ?? 0),
                tamanoLote: (int) ($datos['tamano_lote'] ?? 1000),
                listaId: $listaId,
            );
        }

        return $this->respuestaCompleta($listaId);
    }

    /**
     * Descarga completa: todos los registros activos + lista interna activa.
     */
    private function respuestaCompleta(?int $listaId): JsonResponse
    {
        $queryVinculantes = RegistroLista::with('lista')
            ->where('estado', 'activo')
            ->whereNull('deleted_at');

        if ($listaId) {
            $queryVinculantes->where('lista_id', $listaId);
        }

        $vinculantes = $queryVinculantes->limit(200)->get();

        $interna = ListaNegraInterna::where('estado', 'activo')
            ->whereNull('deleted_at')
            ->get();

        return response()->json([
            'data' => [
                'vinculantes' => RegistroListaResource::collection($vinculantes),
                'lista_interna' => ListaNegraInternaResource::collection($interna),
            ],
            'meta' => [
                'tipo_descarga' => 'completa',
                'total_vinculantes' => $vinculantes->count(),
                'total_interna' => $interna->count(),
                'punto_de_control_actual' => $this->novedadExportacionService->obtenerUltimoPuntoDeControl(),
            ],
        ]);
    }

    /**
     * Descarga por novedades desde cursor/punto_de_control.
     */
    private function respuestaNovedades(int $puntoDeControl, int $tamanoLote, ?int $listaId = null): JsonResponse
    {
        $resultado = $this->novedadExportacionService->obtenerNovedades($puntoDeControl, $tamanoLote, $listaId);
        $novedades = $resultado['novedades'];
        $conteoNovedades = [
            'ingresos' => $novedades->where('tipo_novedad', 'ingreso')->count(),
            'salidas' => $novedades->where('tipo_novedad', 'salida')->count(),
            'actualizados' => $novedades->where('tipo_novedad', 'actualizado')->count(),
        ];

        return response()->json([
            'data' => [
                'novedades' => $novedades->map(static function ($novedad): array {
                    return [
                        'id_novedad' => (int) $novedad->id,
                        'origen_lista' => (string) $novedad->origen_lista,
                        'tipo_novedad' => (string) $novedad->tipo_novedad,
                        'nombre_lista' => (string) $novedad->nombre_lista,
                        'registro_lista_id' => $novedad->registro_lista_id,
                        'lista_negra_id' => $novedad->lista_negra_id,
                        'sincronizacion_log_id' => $novedad->sincronizacion_log_id,
                        'datos' => $novedad->datos,
                        'fecha_evento' => $novedad->created_at?->toIso8601String(),
                    ];
                })->values(),
            ],
            'meta' => [
                'tipo_descarga' => 'novedades',
                'punto_de_control_recibido' => $puntoDeControl,
                'siguiente_punto_de_control' => $resultado['siguiente_punto_de_control'],
                'hay_mas' => $resultado['hay_mas'],
                'tamano_lote' => $tamanoLote,
                'lista_id' => $listaId,
                'novedades' => $conteoNovedades,
                'total_novedades' => $novedades->count(),
            ],
        ]);
    }
}
