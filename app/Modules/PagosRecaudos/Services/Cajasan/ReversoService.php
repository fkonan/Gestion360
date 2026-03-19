<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Models\ConReversoCajasan;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Exception;
use Illuminate\Support\Facades\DB;

class ReversoService
{
    // Estados de fallo con reverso
    private const FALLOS_CON_REVERSO = [
        'fallo_timeout_con_reverso',
        'fallo_timeout_sin_reverso',
    ];

    public static function crear(array $datosReverso, int $codigo): void
    {
        try {
            $nextId = self::obtenerSiguienteId();

            $reverso = new ConReversoCajasan;
            $reverso->id = $nextId;
            $reverso->detalle_id = $datosReverso['transactionId'];
            $reverso->transmission_datetime = $datosReverso['transmissionDateTime'] ?? null;
            $reverso->response_code = $datosReverso['responseCode'];
            $reverso->authorization_rsp_code = $codigo ?? null;
            $reverso->error_id = $datosReverso['errorID'] ?? null;
            $reverso->save();
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al crear reverso', $e, [
                'operation' => 'pago',
                'codigo' => $codigo,
                'detalle_id' => $datosReverso['transactionId'] ?? null,
            ]);
        }
    }

    public static function requiere(array $pagoResponse): bool
    {
        return ! empty($pagoResponse['status']) &&
          in_array($pagoResponse['status'], self::FALLOS_CON_REVERSO) &&
          isset($pagoResponse['reverso']);
    }

    private static function obtenerSiguienteId(): int
    {
        return DB::connection('oracle')->table('CON_REVERSO_CAJASAN')->max('ID') + 1;
    }
}
