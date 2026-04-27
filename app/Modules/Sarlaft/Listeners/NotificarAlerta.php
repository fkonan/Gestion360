<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Listeners;

use App\Modules\Sarlaft\Events\AlertaGenerada;
use Illuminate\Support\Facades\Log;

class NotificarAlerta
{
    public function handle(AlertaGenerada $event): void
    {
        Log::channel('daily')->warning('Alerta generada', [
            'alerta_id' => $event->alerta->id,
            'consulta_id' => $event->alerta->consulta_id,
            'tipo' => $event->alerta->tipo,
            'nivel_riesgo' => $event->alerta->nivel_riesgo,
            'datos_persona' => $event->alerta->datos_persona,
        ]);

        // TODO: Implementar notificación por email/Slack en fase posterior
    }
}
