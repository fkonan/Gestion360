<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Api\ConsultarListaRequest;
use App\Modules\Sarlaft\Http\Requests\Api\ExportarListasRequest;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\NovedadExportacion;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Services\NovedadExportacionService;
use App\Modules\Sarlaft\Services\ValidacionListaNegraService;
use Illuminate\Http\JsonResponse;

class ListaRegistroController extends Controller
{
    public function __construct(
        private readonly NovedadExportacionService $novedadExportacionService,
        private readonly ValidacionListaNegraService $validacionListaNegraService,
    ) {}

    /**
     * Consulta puntual: indica si un documento esta en alguna lista (vinculante
     * o restrictiva). Verificacion en tiempo real para sistemas externos.
     * No registra intento ni alerta: es solo una consulta.
     */
    public function consultar(ConsultarListaRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $resultado = $this->validacionListaNegraService->consultarPorIdentificacion(
            numeroIdentificacion: (string) $datos['numero_documento'],
            tipoDocumento: (string) $datos['tipo_documento'],
        );

        return response()->json([
            'en_lista' => (bool) ($resultado['en_lista_negra'] ?? false),
        ]);
    }

    /**
     * Exportacion de listas vinculantes e internas.
     *
     * - tipo_descarga=completa: entrega catalogo completo activo.
     * - tipo_descarga=novedades: entrega delta por rango de fecha.
     */
    public function index(ExportarListasRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $tipoDescarga = (string) $datos['tipo_descarga'];
        $page = isset($datos['page']) ? (int) $datos['page'] : 1;

        if ($tipoDescarga === 'novedades') {
            return $this->respuestaNovedades(
                fechaDesde: (string) $datos['fecha_desde'],
                page: $page,
            );
        }

        return $this->respuestaCompleta($page);
    }

    /**
     * Descarga completa: todos los registros activos + lista interna activa.
     */
    private function respuestaCompleta(int $page): JsonResponse
    {
        $vinculantes = RegistroLista::with('lista')
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->paginate(1000, ['*'], 'page', $page);

        $interna = ListaNegraInterna::where('estado', 'activo')
            ->whereNull('deleted_at')
            ->paginate(1000, ['*'], 'page', $page);

        $registros = $vinculantes->getCollection()
            ->map(fn ($r) => $this->mapearVinculante($r))
            ->merge(
                $interna->getCollection()->map(fn ($r) => $this->mapearInterna($r))
            )
            ->values();

        return response()->json([
            'data' => [
                'registros' => $registros,
            ],
            'meta' => [
                'tipo_descarga' => 'completa',
                'total_vinculantes' => $vinculantes->total(),
                'total_interna' => $interna->total(),
                'total' => $vinculantes->total() + $interna->total(),
                'paginacion' => [
                    'pagina_actual' => $page,
                    'ultima_pagina' => max($vinculantes->lastPage(), $interna->lastPage()),
                ],
            ],
        ]);
    }

    /**
     * Descarga por novedades en un rango de fechas.
     */
    private function respuestaNovedades(string $fechaDesde, int $page = 1): JsonResponse
    {
        $resultado = $this->novedadExportacionService->obtenerNovedades($fechaDesde, $page);
        $paginador = $resultado['novedades'];

        $registros = $paginador->getCollection()
            ->map(fn ($n) => $this->mapearNovedad($n))
            ->values();

        return response()->json([
            'data' => [
                'registros' => $registros,
            ],
            'meta' => [
                'tipo_descarga' => 'novedades',
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => now()->toDateString(),
                'novedades' => $resultado['conteo_por_tipo'],
                'total_novedades' => $resultado['total'],
                'paginacion' => [
                    'pagina_actual' => $paginador->currentPage(),
                    'ultima_pagina' => $paginador->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearVinculante(RegistroLista $r): array
    {
        return [
            'id' => $r->id,
            'tipo_novedad' => $r->novedad ?? 'sin_cambio',
            'origen' => 'vinculante',
            'lista' => $r->relationLoaded('lista') ? $r->lista?->nombre : null,
            'tipo_entidad' => $r->tipo_entidad,
            'nombres' => $r->nombres,
            'alias' => $r->alias,
            'identificacion' => $r->identificacion,
            'tipo_identificacion' => $r->tipo_identificacion,
            'fecha_nacimiento' => $r->fecha_nacimiento?->format('Y-m-d'),
            'pais' => $r->pais,
            'motivo' => $r->motivo,
            'fecha_inclusion' => $r->fecha_inclusion?->format('Y-m-d'),
            'estado' => $r->estado,
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearInterna(ListaNegraInterna $r): array
    {
        return [
            'id' => $r->id,
            'tipo_novedad' => 'sin_cambio',
            'origen' => 'interna',
            'lista' => 'copetran',
            'tipo_entidad' => $r->tipo_entidad,
            'nombres' => $r->nombres,
            'alias' => null,
            'identificacion' => $r->numero_documento,
            'tipo_identificacion' => $r->tipo_documento,
            'fecha_nacimiento' => null,
            'pais' => null,
            'motivo' => $r->motivo,
            'fecha_inclusion' => null,
            'estado' => $r->estado,
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearNovedad(NovedadExportacion $n): array
    {
        $datos = is_array($n->datos) ? $n->datos : [];

        return [
            'id_novedad' => $n->id,
            'registro_lista_id' => $n->registro_lista_id,
            'tipo_novedad' => $n->tipo_novedad,
            'origen' => $n->origen_lista,
            'lista' => $n->nombre_lista,
            'tipo_entidad' => $datos['tipo_entidad'] ?? null,
            'nombres' => $datos['nombres'] ?? null,
            'alias' => $datos['alias'] ?? null,
            'identificacion' => $datos['identificacion'] ?? null,
            'tipo_identificacion' => $datos['tipo_identificacion'] ?? null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            'pais' => $datos['pais'] ?? null,
            'motivo' => $datos['motivo'] ?? null,
            'fecha_inclusion' => $datos['fecha_inclusion'] ?? null,
            'estado' => $datos['estado'] ?? null,
            'updated_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
