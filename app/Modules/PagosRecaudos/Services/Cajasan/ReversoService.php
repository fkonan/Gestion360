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
        'fallo_incierto_con_reverso',
        'fallo_incierto_sin_reverso',
    ];

    public static function crear(array $datosReverso, ?int $codigo = null, array $contextoTransaccion = []): void
    {
        try {
            $nextId = self::obtenerSiguienteId();

            $reverso = new ConReversoCajasan;
            $reverso->id = $nextId;
            $reverso->detalle_id = $datosReverso['transactionId'];
            $reverso->transmission_datetime = $datosReverso['transmissionDateTime'] ?? null;
            $reverso->identification_type = $contextoTransaccion['identification_type'] ?? null;
            $reverso->identification = $contextoTransaccion['identification'] ?? null;
            $reverso->amount_tran = $contextoTransaccion['amount_tran'] ?? null;
            $reverso->state_code = $contextoTransaccion['state_code'] ?? null;
            $reverso->city_code = $contextoTransaccion['city_code'] ?? null;
            $reverso->sequence_id = $datosReverso['sequenceId'] ?? null;
            $reverso->status = $datosReverso['status'] ?? null;
            $reverso->response_code = $datosReverso['responseCode'] ?? false;
            $reverso->authorization_rsp_code = $datosReverso['authorizationRspCode'] ?? $codigo;
            $reverso->error_id = $datosReverso['errorID'] ?? null;
            $reverso->error_message = self::resolverMensajeError($datosReverso);
            $reverso->additional_data = self::serializarAdditionalData($datosReverso, $codigo, $contextoTransaccion);
            $reverso->save();
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al crear reverso', $e, [
                'operation' => 'pago',
                'codigo' => $codigo,
                'detalle_id' => $datosReverso['transactionId'] ?? null,
                'response_code' => $datosReverso['responseCode'] ?? null,
                'authorization_rsp_code' => $datosReverso['authorizationRspCode'] ?? null,
                'error_id' => $datosReverso['errorID'] ?? null,
                'sequence_id' => $datosReverso['sequenceId'] ?? null,
                'status' => $datosReverso['status'] ?? null,
                'error' => $datosReverso['error'] ?? null,
                'identification_type' => $contextoTransaccion['identification_type'] ?? null,
                'identification' => $contextoTransaccion['identification'] ?? null,
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
        return DB::connection('oracle')->table('LOGTRANSPRO.CON_REVERSO_CAJASAN')->max('ID') + 1;
    }

    private static function resolverMensajeError(array $datosReverso): ?string
    {
        $candidatos = [
            $datosReverso['error'] ?? null,
            $datosReverso['message'] ?? null,
            $datosReverso['additionalData']['validationError'] ?? null,
            $datosReverso['additionalData']['errorMessage'] ?? null,
            $datosReverso['additionalData']['errorMesssage'] ?? null,
        ];

        foreach ($candidatos as $valor) {
            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return null;
    }

    private static function serializarAdditionalData(array $datosReverso, ?int $codigo, array $contextoTransaccion): ?string
    {
        $payload = [
            'response_body' => $datosReverso,
            'authorization_rsp_code_fallback' => $codigo,
            'request_context' => $contextoTransaccion,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
