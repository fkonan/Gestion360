<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Resources\ListaNegraInternaResource;
use App\Modules\Sarlaft\Http\Resources\RegistroListaResource;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Models\SincronizacionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListaRegistroController extends Controller
{
    /**
     * Descarga de registros de listas vinculantes + lista interna.
     *
     * Sin ?fecha → descarga completa (todos los activos).
     * Con ?fecha=YYYY-MM-DD → novedades de esa sincronización (o la última anterior).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => 'nullable|date_format:Y-m-d',
            'lista_id' => 'nullable|integer|exists:mysql-sarlaft.sarlaft_listas_vinculantes,id',
        ]);

        $fecha = $request->query('fecha');
        $listaId = $request->query('lista_id');

        if ($fecha) {
            return $this->respuestaNovedades($fecha, $listaId);
        }

        return $this->respuestaCompleta($listaId);
    }

    /**
     * Descarga completa: todos los registros activos + lista interna activa.
     */
    private function respuestaCompleta(?string $listaId): JsonResponse
    {
        $queryVinculantes = RegistroLista::with('lista')
            ->where('estado', 'activo')
            ->whereNull('deleted_at');

        if ($listaId) {
            $queryVinculantes->where('lista_id', (int) $listaId);
        }

        $vinculantes = $queryVinculantes->get();

        $interna = ListaNegraInterna::where('estado', 'activo')
            ->whereNull('deleted_at')
            ->get();

        $ultimaSync = SincronizacionLog::where('estado', 'exitoso')
            ->latest('created_at')
            ->first();

        return response()->json([
            'data' => [
                'vinculantes' => RegistroListaResource::collection($vinculantes),
                'lista_interna' => ListaNegraInternaResource::collection($interna),
            ],
            'meta' => [
                'tipo_descarga' => 'completa',
                'fecha_sincronizacion' => $ultimaSync?->created_at?->toIso8601String(),
                'total_vinculantes' => $vinculantes->count(),
                'total_interna' => $interna->count(),
            ],
        ]);
    }

    /**
     * Descarga por novedades: busca la sync de esa fecha (o la última anterior)
     * y retorna solo registros con novedad distinta a sin_cambio.
     */
    private function respuestaNovedades(string $fecha, ?string $listaId): JsonResponse
    {
        // Buscar el log de sincronización de esa fecha o el inmediatamente anterior
        $logQuery = SincronizacionLog::where('estado', 'exitoso')
            ->whereDate('created_at', '<=', $fecha)
            ->latest('created_at');

        if ($listaId) {
            $logQuery->where('lista_id', (int) $listaId);
        }

        $log = $logQuery->first();

        if (! $log) {
            return response()->json([
                'data' => [
                    'vinculantes' => [],
                    'lista_interna' => [],
                ],
                'meta' => [
                    'tipo_descarga' => 'novedades',
                    'fecha_solicitada' => $fecha,
                    'fecha_sincronizacion' => null,
                    'mensaje' => 'No se encontró sincronización para la fecha indicada ni anterior.',
                    'novedades' => ['ingresos' => 0, 'salidas' => 0, 'actualizados' => 0],
                    'total_vinculantes' => 0,
                    'total_interna' => 0,
                ],
            ]);
        }

        // Si no se filtró por lista, obtener todos los logs de esa misma fecha
        $logIds = SincronizacionLog::where('estado', 'exitoso')
            ->whereDate('created_at', $log->created_at->toDateString());

        if ($listaId) {
            $logIds->where('lista_id', (int) $listaId);
        }

        $syncLogIds = $logIds->pluck('id');

        // Registros vinculantes con novedad en esa sync (excluyendo sin_cambio)
        $vinculantes = RegistroLista::withTrashed()
            ->with('lista')
            ->whereIn('sincronizacion_log_id', $syncLogIds)
            ->whereIn('novedad', ['ingreso', 'salida', 'actualizado'])
            ->get();

        // Lista interna: cambios desde esa fecha de sync
        $fechaSync = $log->created_at->startOfDay();
        $interna = ListaNegraInterna::withTrashed()
            ->where(function ($q) use ($fechaSync) {
                $q->where('updated_at', '>=', $fechaSync)
                    ->orWhere('deleted_at', '>=', $fechaSync);
            })
            ->get();

        $conteoNovedades = [
            'ingresos' => $vinculantes->where('novedad', 'ingreso')->count(),
            'salidas' => $vinculantes->where('novedad', 'salida')->count(),
            'actualizados' => $vinculantes->where('novedad', 'actualizado')->count(),
        ];

        return response()->json([
            'data' => [
                'vinculantes' => RegistroListaResource::collection($vinculantes),
                'lista_interna' => ListaNegraInternaResource::collection($interna),
            ],
            'meta' => [
                'tipo_descarga' => 'novedades',
                'fecha_solicitada' => $fecha,
                'fecha_sincronizacion' => $log->created_at->toIso8601String(),
                'novedades' => $conteoNovedades,
                'total_vinculantes' => $vinculantes->count(),
                'total_interna' => $interna->count(),
            ],
        ]);
    }
}
