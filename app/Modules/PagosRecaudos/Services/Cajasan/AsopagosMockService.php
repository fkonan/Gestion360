<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

class AsopagosMockService
{
    private const MOCK_AUTHORIZATION_CODE = 654321;

    private const MOCK_REVERSO_AUTHORIZATION_CODE = 765432;

    public function consultaSaldo(array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        return match ($runtime->consultaSaldoScenario()) {
            'success_without_balance' => $this->consultaSaldoExitosa(0),
            'provider_error' => $this->respuestaErrorConsulta(
                'Error no registrado por Asopagos (simulado).'
            ),
            default => $this->consultaSaldoExitosa($runtime->mockSaldo()),
        };
    }

    public function pago(int $idPagoDetalle, array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        return match ($runtime->pagoScenario()) {
            'timeout_with_reverse' => $this->respuestaTimeoutPago(
                $idPagoDetalle,
                'fallo_timeout_con_reverso',
                $this->reversoExitoso($idPagoDetalle)
            ),
            'timeout_without_reverse' => $this->respuestaTimeoutPago(
                $idPagoDetalle,
                'fallo_timeout_sin_reverso',
                $this->reversoFallido(
                    $idPagoDetalle,
                    'La transaccion no pudo ser reversada (simulado).'
                )
            ),
            default => $this->respuestaPagoExitosa($idPagoDetalle),
        };
    }

    private function consultaSaldoExitosa(int $saldo): array
    {
        return [
            'responseCode' => true,
            'additionalData' => [
                'saldo' => $saldo,
            ],
        ];
    }

    private function respuestaErrorConsulta(string $mensaje): array
    {
        return [
            'responseCode' => false,
            'additionalData' => $this->additionalDataError($mensaje),
        ];
    }

    private function respuestaPagoExitosa(int $idPagoDetalle): array
    {
        return array_merge($this->baseTransactionPayload($idPagoDetalle), [
            'responseCode' => true,
            'authorizationRspCode' => self::MOCK_AUTHORIZATION_CODE,
        ]);
    }

    private function respuestaTimeoutPago(int $idPagoDetalle, string $status, array $reverso): array
    {
        return array_merge($this->baseTransactionPayload($idPagoDetalle), [
            'error' => 'cURL error 28: Operation timed out (simulado)',
            'responseCode' => false,
            'status' => $status,
            'is_uncertain' => true,
            'transport_error' => true,
            'error_category' => 'timeout',
            'reverso' => $reverso,
        ]);
    }

    private function reversoExitoso(int $idPagoDetalle): array
    {
        return array_merge($this->baseTransactionPayload($idPagoDetalle), [
            'responseCode' => true,
            'status' => 'reverse_success',
            'authorizationRspCode' => self::MOCK_REVERSO_AUTHORIZATION_CODE,
            'additionalData' => [],
        ]);
    }

    private function reversoFallido(int $idPagoDetalle, string $mensaje): array
    {
        return array_merge($this->baseTransactionPayload($idPagoDetalle), [
            'responseCode' => false,
            'status' => 'reverse_failed',
            'additionalData' => $this->additionalDataError($mensaje),
        ]);
    }

    private function baseTransactionPayload(int $idPagoDetalle): array
    {
        return [
            'transactionId' => $idPagoDetalle,
            'sequenceId' => $idPagoDetalle,
            'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
        ];
    }

    private function additionalDataError(string $mensaje): array
    {
        return [
            'validationError' => $mensaje,
            'errorMessage' => $mensaje,
            'errorMesssage' => $mensaje,
        ];
    }
}
