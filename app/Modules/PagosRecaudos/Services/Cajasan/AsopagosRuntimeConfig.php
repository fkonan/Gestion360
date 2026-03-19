<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

class AsopagosRuntimeConfig
{
    public const PROVIDER_REAL = 'real';

    public const PROVIDER_MOCK = 'mock';

    public const PERSISTENCE_REAL = 'real';

    public const PERSISTENCE_READONLY = 'readonly';

    public function providerMode(): string
    {
        $mode = (string) config('apiAsopagos.provider_mode', self::PROVIDER_REAL);

        return in_array($mode, [self::PROVIDER_REAL, self::PROVIDER_MOCK], true)
            ? $mode
            : self::PROVIDER_REAL;
    }

    public function persistenceMode(): string
    {
        $mode = (string) config('apiAsopagos.persistence_mode', self::PERSISTENCE_REAL);

        return in_array($mode, [self::PERSISTENCE_REAL, self::PERSISTENCE_READONLY], true)
            ? $mode
            : self::PERSISTENCE_REAL;
    }

    public function shouldMockProvider(): bool
    {
        return $this->providerMode() === self::PROVIDER_MOCK;
    }

    public function shouldUseRealPersistence(): bool
    {
        return $this->persistenceMode() === self::PERSISTENCE_REAL;
    }

    public function isDangerousMockConfiguration(): bool
    {
        return $this->shouldMockProvider() && $this->shouldUseRealPersistence();
    }

    public function consultaSaldoScenario(): string
    {
        return (string) config('apiAsopagos.mock.consulta_saldo_scenario', 'success_with_balance');
    }

    public function pagoScenario(): string
    {
        return (string) config('apiAsopagos.mock.pago_scenario', 'success');
    }

    public function mockSaldo(): int
    {
        return max(0, (int) config('apiAsopagos.mock.saldo', 79000));
    }

    public function context(): array
    {
        return [
            'provider_mode' => $this->providerMode(),
            'persistence_mode' => $this->persistenceMode(),
            'legacy_test_mode' => (bool) config('apiAsopagos.test_mode'),
            'mock_consulta_saldo_scenario' => $this->consultaSaldoScenario(),
            'mock_pago_scenario' => $this->pagoScenario(),
        ];
    }
}
