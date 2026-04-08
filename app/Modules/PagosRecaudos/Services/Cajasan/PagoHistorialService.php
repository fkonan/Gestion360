<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class PagoHistorialService
{
    private const ESTADO_PAGADO = 'P';

    private const ESTADO_ANULADO = 'A';

    private const ESTADOS_VISIBLES = [
        self::ESTADO_PAGADO,
        self::ESTADO_ANULADO,
    ];

    private const FILTRO_TODOS = 'todos';

    private const FILTRO_PAGADO = 'pagado';

    private const FILTRO_FALLIDO = 'fallido';

    private const PER_PAGE = 20;

    public function obtenerPagosDelDia(?object $cajaActiva, int $userId, array $filters, bool $alcanceGlobal = false): Paginator
    {
        $startOfDay = now('America/Bogota')->startOfDay();
        $endOfDay = now('America/Bogota')->endOfDay();
        $estadoFiltro = $this->normalizarEstadoFiltro($filters['estado'] ?? self::FILTRO_TODOS);
        $textoBusqueda = trim((string) ($filters['q'] ?? ''));

        try {
            $connection = DB::connection('oracle');

            $pagadosQuery = $this->construirConsultaPagados($connection, $cajaActiva, $startOfDay, $endOfDay, $alcanceGlobal);
            $fallidosQuery = $this->construirConsultaFallidos($connection, $cajaActiva, $userId, $startOfDay, $endOfDay, $alcanceGlobal);

            if ($estadoFiltro === self::FILTRO_PAGADO) {
                $query = $connection->query()->fromSub($pagadosQuery, 'H')->select('H.*');
            } elseif ($estadoFiltro === self::FILTRO_FALLIDO) {
                $query = $connection->query()->fromSub($fallidosQuery, 'H')->select('H.*');
            } else {
                $query = $connection->query()
                    ->fromSub($pagadosQuery->unionAll($fallidosQuery), 'H')
                    ->select('H.*');
            }

            if ($textoBusqueda !== '') {
                $textoBusquedaLike = '%'.mb_strtoupper($textoBusqueda).'%';

                $query->where(function ($searchQuery) use ($textoBusqueda, $textoBusquedaLike) {
                    $searchQuery->whereRaw('UPPER(H.CLIENTE) LIKE ?', [$textoBusquedaLike]);

                    if (is_numeric($textoBusqueda)) {
                        $searchQuery->orWhere('H.IDENTIFICACION', $textoBusqueda)
                            ->orWhere('H.NRO_INTERNO', $textoBusqueda)
                            ->orWhere('H.COMPROBANTE', $textoBusqueda);
                    }
                });
            }

            $pagos = $query
                ->orderByDesc('H.FECCREACION')
                ->orderByDesc('H.ID')
                ->simplePaginate(self::PER_PAGE)
                ->withQueryString();

            return $pagos;
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar historial de pagos del dia', $e, [
                'operation' => 'historial_hoy',
                'caja_activa_id' => $cajaActiva->id ?? null,
                'usuario_id' => $userId,
                'estado' => $estadoFiltro,
                'q' => $textoBusqueda,
                'alcance_global' => $alcanceGlobal,
            ]);

            throw new Exception('No fue posible cargar el historial de pagos del dia.');
        }
    }

    public static function etiquetaEstado(?string $estado): string
    {
        return match ($estado) {
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_ANULADO => 'Fallido/Anulado',
            default => 'Desconocido',
        };
    }

    private function normalizarEstadoFiltro(?string $estado): string
    {
        return match ($estado) {
            self::FILTRO_PAGADO => self::FILTRO_PAGADO,
            self::FILTRO_FALLIDO => self::FILTRO_FALLIDO,
            default => self::FILTRO_TODOS,
        };
    }

    private function construirConsultaPagados($connection, ?object $cajaActiva, $startOfDay, $endOfDay, bool $alcanceGlobal = false)
    {
        $query = $connection
            ->table('CON_DETCARGUEPAGOSYRECAUDOS as D')
            ->join('CON_DETALLEPAGORECAUDO as DPR', function ($join) {
                $join->on('DPR.ID_DET_CARPAGYREC', '=', 'D.ID')
                    ->where('DPR.ESTBORRADO', 0);
            })
            ->join('CON_COMPROBANTES as CP', function ($join) {
                $join->on('CP.ID', '=', 'DPR.CP_ID')
                    ->where('CP.ESTBORRADO', 0);
            })
            ->select([
                'D.ID as id',
                'D.FECCREACION as feccreacion',
                'D.IDEN_CLIENTEPRINCIPAL as identificacion',
                'D.CLIENTEPRINCIPAL as cliente',
                'D.VALORTOTAL as valor_total',
                'D.ESTADO as estado',
                'D.NRO_INTERNO as nro_interno',
                'D.USRCREACION as usrcreacion',
                'D.EMPCREACION as empcreacion',
                'D.AGENCIA as agencia',
                'DPR.TELEFONO as telefono',
                'CP.COMPROBANTE as comprobante',
                'CP.CT_ID as comprobante_ct_id',
            ])
            ->where('D.ESTBORRADO', 0)
            ->where('D.ESTADO', self::ESTADO_PAGADO)
            ->whereBetween('D.FECCREACION', [$startOfDay, $endOfDay]);

        if (! $alcanceGlobal) {
            $query->where('CP.CT_ID', $cajaActiva?->id ?? 0);
        }

        return $query;
    }

    private function construirConsultaFallidos($connection, ?object $cajaActiva, int $userId, $startOfDay, $endOfDay, bool $alcanceGlobal = false)
    {
        $query = $connection
            ->table('CON_DETCARGUEPAGOSYRECAUDOS as D')
            ->select([
                'D.ID as id',
                'D.FECCREACION as feccreacion',
                'D.IDEN_CLIENTEPRINCIPAL as identificacion',
                'D.CLIENTEPRINCIPAL as cliente',
                'D.VALORTOTAL as valor_total',
                'D.ESTADO as estado',
                'D.NRO_INTERNO as nro_interno',
                'D.USRCREACION as usrcreacion',
                'D.EMPCREACION as empcreacion',
                'D.AGENCIA as agencia',
                DB::raw('NULL as telefono'),
                DB::raw('NULL as comprobante'),
                DB::raw('NULL as comprobante_ct_id'),
            ])
            ->where('D.ESTBORRADO', 0)
            ->where('D.ESTADO', self::ESTADO_ANULADO)
            ->whereBetween('D.FECCREACION', [$startOfDay, $endOfDay]);

        if (! $alcanceGlobal) {
            $query->where('D.USRCREACION', $userId)
                ->where('D.EMPCREACION', $cajaActiva?->idsucursal ?? 0);
        }

        return $query;
    }
}
