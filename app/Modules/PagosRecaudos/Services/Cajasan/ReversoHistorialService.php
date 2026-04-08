<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class ReversoHistorialService
{
    private const FILTRO_TODOS = 'todos';

    private const FILTRO_EXITOSO = 'exitoso';

    private const FILTRO_FALLIDO = 'fallido';

    private const PER_PAGE = 20;

    public function obtenerReversos(array $filters): Paginator
    {
        $textoBusqueda = trim((string) ($filters['q'] ?? ''));
        $resultadoFiltro = $this->normalizarResultadoFiltro($filters['resultado'] ?? self::FILTRO_TODOS);

        try {
            $query = DB::connection('oracle')
                ->table('LOGTRANSPRO.CON_REVERSO_CAJASAN as R')
                ->leftJoin('CON_DETCARGUEPAGOSYRECAUDOS as D', function ($join) {
                    $join->on('D.ID', '=', 'R.DETALLE_ID')
                        ->where('D.ESTBORRADO', 0);
                })
                ->select([
                    'R.ID as id',
                    'R.DETALLE_ID as detalle_id',
                    'R.TRANSMISSION_DATETIME as transmission_datetime',
                    'R.IDENTIFICATION_TYPE as identification_type',
                    'R.IDENTIFICATION as identification',
                    'R.AMOUNT_TRAN as amount_tran',
                    'R.STATE_CODE as state_code',
                    'R.CITY_CODE as city_code',
                    'R.SEQUENCE_ID as sequence_id',
                    'R.STATUS as status',
                    'R.RESPONSE_CODE as response_code',
                    'R.AUTHORIZATION_RSP_CODE as authorization_rsp_code',
                    'R.ERROR_ID as error_id',
                    'R.ERROR_MESSAGE as error_message',
                    'D.CLIENTEPRINCIPAL as cliente',
                    'D.IDEN_CLIENTEPRINCIPAL as identificacion_cliente',
                    'D.VALORTOTAL as valor_total',
                    'D.ESTADO as estado_pago_local',
                    'D.NRO_INTERNO as nro_interno',
                ]);

            if ($resultadoFiltro === self::FILTRO_EXITOSO) {
                $query->where('R.RESPONSE_CODE', true);
            } elseif ($resultadoFiltro === self::FILTRO_FALLIDO) {
                $query->where(function ($subQuery) {
                    $subQuery->where('R.RESPONSE_CODE', false)
                        ->orWhereNull('R.RESPONSE_CODE');
                });
            }

            if ($textoBusqueda !== '') {
                $textoBusquedaLike = '%'.mb_strtoupper($textoBusqueda).'%';

                $query->where(function ($subQuery) use ($textoBusqueda, $textoBusquedaLike) {
                    $subQuery->whereRaw('UPPER(NVL(D.CLIENTEPRINCIPAL, \'SIN CLIENTE\')) LIKE ?', [$textoBusquedaLike]);

                    if (is_numeric($textoBusqueda)) {
                        $subQuery->orWhere('R.DETALLE_ID', $textoBusqueda)
                            ->orWhere('R.SEQUENCE_ID', $textoBusqueda)
                            ->orWhere('R.AUTHORIZATION_RSP_CODE', $textoBusqueda)
                            ->orWhere('D.IDEN_CLIENTEPRINCIPAL', $textoBusqueda)
                            ->orWhere('R.IDENTIFICATION', $textoBusqueda);
                    }
                });
            }

            return $query
                ->orderByDesc('R.ID')
                ->simplePaginate(self::PER_PAGE)
                ->withQueryString();
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al cargar historial de reversos', $e, [
                'operation' => 'historial_reversos',
                'resultado' => $resultadoFiltro,
                'q' => $textoBusqueda,
            ]);

            throw new Exception('No fue posible cargar el historial de reversos.');
        }
    }

    public static function etiquetaResultado($responseCode): string
    {
        return filter_var($responseCode, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true
            ? 'Exitoso'
            : 'Fallido';
    }

    public static function situacionPago($responseCode, ?string $estadoPagoLocal): string
    {
        $reversoExitoso = filter_var($responseCode, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;

        if ($reversoExitoso && $estadoPagoLocal === 'A') {
            return 'Pago anulado y reversado correctamente';
        }

        if ($reversoExitoso) {
            return 'Reverso exitoso; verificar estado local del pago';
        }

        if ($estadoPagoLocal === 'A') {
            return 'Pago anulado localmente, reverso fallido o pendiente';
        }

        return 'Requiere revision con mesa de ayuda';
    }

    private function normalizarResultadoFiltro(?string $resultado): string
    {
        return match ($resultado) {
            self::FILTRO_EXITOSO => self::FILTRO_EXITOSO,
            self::FILTRO_FALLIDO => self::FILTRO_FALLIDO,
            default => self::FILTRO_TODOS,
        };
    }
}
