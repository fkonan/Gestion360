<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

class AsopagosRuntimeConfig
{
    private const CONSULTA_SALDO_SCENARIOS = [
        'success_with_balance',
        'success_without_balance',
        'provider_error',
    ];

    private const PAGO_SCENARIOS = [
        'success',
        'timeout_with_reverse',
        'timeout_without_reverse',
    ];

    public const PROVIDER_REAL = 'real';

    public const PROVIDER_MOCK = 'mock';

    public const PERSISTENCE_REAL = 'real';

    public const PERSISTENCE_READONLY = 'readonly';

    public function providerMode(): string
    {
        $mode = $this->configuredProviderMode();

        if ($this->isProduction()) {
            return self::PROVIDER_REAL;
        }

        return $mode;
    }

    public function persistenceMode(): string
    {
        $mode = $this->configuredPersistenceMode();

        if ($this->isProduction()) {
            return self::PERSISTENCE_REAL;
        }

        return $mode;
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
        $scenario = (string) config('apiAsopagos.mock.consulta_saldo_scenario', 'success_with_balance');

        return in_array($scenario, self::CONSULTA_SALDO_SCENARIOS, true)
            ? $scenario
            : 'success_with_balance';
    }

    public function pagoScenario(): string
    {
        $scenario = (string) config('apiAsopagos.mock.pago_scenario', 'success');

        return in_array($scenario, self::PAGO_SCENARIOS, true)
            ? $scenario
            : 'success';
    }

    public function mockSaldo(): int
    {
        return max(0, (int) config('apiAsopagos.mock.saldo', 79000));
    }

    public function context(): array
    {
        return [
            'app_env' => app()->environment(),
            'provider_mode' => $this->providerMode(),
            'persistence_mode' => $this->persistenceMode(),
            'configured_provider_mode' => $this->configuredProviderMode(),
            'configured_persistence_mode' => $this->configuredPersistenceMode(),
            'mock_consulta_saldo_scenario' => $this->consultaSaldoScenario(),
            'mock_pago_scenario' => $this->pagoScenario(),
        ];
    }

    private function configuredProviderMode(): string
    {
        $mode = (string) config('apiAsopagos.provider_mode', self::PROVIDER_REAL);

        return in_array($mode, [self::PROVIDER_REAL, self::PROVIDER_MOCK], true)
            ? $mode
            : self::PROVIDER_REAL;
    }

    private function configuredPersistenceMode(): string
    {
        $mode = (string) config('apiAsopagos.persistence_mode', self::PERSISTENCE_REAL);

        return in_array($mode, [self::PERSISTENCE_REAL, self::PERSISTENCE_READONLY], true)
            ? $mode
            : self::PERSISTENCE_REAL;
    }

    private function isProduction(): bool
    {
        return app()->environment('production');
    }
}
