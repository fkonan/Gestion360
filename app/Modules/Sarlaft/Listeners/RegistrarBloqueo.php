<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Listeners;

use App\Modules\Sarlaft\Events\BloqueoCreado;
use Illuminate\Support\Facades\Log;

class RegistrarBloqueo
{
    public function handle(BloqueoCreado $event): void
    {
        Log::channel('daily')->warning('Bloqueo creado', [
            'bloqueo_id' => $event->bloqueo->id,
            'tipo_documento' => $event->bloqueo->tipo_documento,
            'numero_documento' => $event->bloqueo->numero_documento,
            'tipo_bloqueo' => $event->bloqueo->tipo_bloqueo,
            'motivo' => $event->bloqueo->motivo_bloqueo,
        ]);
    }
}
