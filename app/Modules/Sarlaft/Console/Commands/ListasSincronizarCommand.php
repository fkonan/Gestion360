<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Jobs\SincronizarListaJob;
use App\Modules\Sarlaft\Models\ListaVinculante;
use Illuminate\Console\Command;

class ListasSincronizarCommand extends Command
{
    protected $signature = 'listas:sincronizar';

    protected $description = 'Despachar jobs de sincronizacion para todas las listas SARLAFT activas';

    public function handle(): int
    {
        $listas = ListaVinculante::query()->where('activa', true)->get();

        foreach ($listas as $lista) {
            SincronizarListaJob::dispatch($lista);
            $this->info("Job despachado para: {$lista->nombre}");
        }

        $this->info("Total: {$listas->count()} listas encoladas.");

        return self::SUCCESS;
    }
}
