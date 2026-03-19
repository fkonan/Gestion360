<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

class AsopagosMockService
{
    public function consultaSaldo(array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        return match ($runtime->consultaSaldoScenario()) {
            'success_without_balance' => [
                'responseCode' => true,
                'additionalData' => ['saldo' => 0],
            ],
            'provider_error' => [
                'responseCode' => false,
                'additionalData' => [
                    'errorMesssage' => 'Proveedor no disponible (simulado).',
                ],
            ],
            default => [
                'responseCode' => true,
                'additionalData' => ['saldo' => $runtime->mockSaldo()],
            ],
        };
    }

    public function pago(int $idPagoDetalle, array $clienteData, AsopagosRuntimeConfig $runtime): array
    {
        return match ($runtime->pagoScenario()) {
            'reject' => [
                'error' => 'Pago rechazado por proveedor (simulado).',
                'responseCode' => false,
            ],
            'timeout_with_reverse' => [
                'error' => 'cURL error 28: Operation timed out (simulado)',
                'responseCode' => true,
                'status' => 'fallo_timeout_con_reverso',
                'authorizationRspCode' => 444444,
                'reverso' => $this->reversoPayload($idPagoDetalle, true, 444444),
            ],
            'timeout_without_reverse' => [
                'error' => 'cURL error 28: Operation timed out (simulado)',
                'responseCode' => true,
                'status' => 'fallo_timeout_sin_reverso',
                'authorizationRspCode' => 555555,
                'reverso' => $this->reversoPayload($idPagoDetalle, false, 555555),
            ],
            'provider_error' => [
                'error' => 'Error de comunicacion con la API (simulado).',
                'responseCode' => false,
            ],
            default => [
                'transactionId' => $idPagoDetalle,
                'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
                'responseCode' => true,
                'authorizationRspCode' => 654321,
                'errorID' => 'E1',
            ],
        };
    }

    private function reversoPayload(int $idPagoDetalle, bool $success, int $authorizationCode): array
    {
        return [
            'transactionId' => $idPagoDetalle,
            'transmissionDateTime' => now()->format('Y-m-d H:i:s'),
            'responseCode' => $success,
            'authorizationRspCode' => $authorizationCode,
            'errorID' => $success ? '00' : '99',
        ];
    }
}
