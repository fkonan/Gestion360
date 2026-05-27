<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class PagoProcesoLockService
{
    private const PAYMENT_EXECUTION_TTL_SECONDS = 120;

    private const SEQUENCE_CRITICAL_SECTION_TTL_SECONDS = 30;

    private const DEFAULT_WAIT_SECONDS = 10;

    public function acquirePaymentExecutionLock(string $uuid): ?Lock
    {
        $lock = Cache::lock($this->key("payment:{$uuid}"), self::PAYMENT_EXECUTION_TTL_SECONDS);

        return $lock->get() ? $lock : null;
    }

    public function release(?Lock $lock): void
    {
        if ($lock) {
            $lock->release();
        }
    }

    public function runSequenceCriticalSection(callable $callback, int $waitSeconds = self::DEFAULT_WAIT_SECONDS): mixed
    {
        return Cache::lock($this->key('sequence-critical-section'), self::SEQUENCE_CRITICAL_SECTION_TTL_SECONDS)
            ->block($waitSeconds, $callback);
    }

    public function runNamedCriticalSection(string $name, callable $callback, int $ttlSeconds = 10, int $waitSeconds = 5): mixed
    {
        return Cache::lock($this->key($name), $ttlSeconds)
            ->block($waitSeconds, $callback);
    }

    private function key(string $suffix): string
    {
        return "pagos-recaudos:{$suffix}";
    }
}
