<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Jobs;

use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Services\SincronizacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SincronizarListaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 300;

    public function __construct(
        public readonly ListaVinculante $lista,
    ) {
        $this->onQueue('sincronizacion');
    }

    public function handle(SincronizacionService $service): void
    {
        Log::info("Iniciando sincronización de lista: {$this->lista->nombre}");
        $log = $service->sincronizar($this->lista);

        Log::info("Sincronización completada para {$this->lista->nombre}", [
            'estado' => $log->estado,
            'procesados' => $log->registros_procesados,
            'nuevos' => $log->registros_nuevos,
            'actualizados' => $log->registros_actualizados,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Job de sincronización fallido para lista {$this->lista->id}: {$exception->getMessage()}");
    }
}
