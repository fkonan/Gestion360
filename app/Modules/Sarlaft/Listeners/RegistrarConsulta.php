<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Listeners;

use App\Modules\Sarlaft\Events\ConsultaRealizada;
use Illuminate\Support\Facades\Log;

class RegistrarConsulta
{
    public function handle(ConsultaRealizada $event): void
    {
        Log::channel('daily')->info('Consulta realizada', [
            'consulta_id' => $event->consulta->id,
            'sistema_origen' => $event->consulta->sistema_origen,
            'tipo_documento' => $event->consulta->tipo_documento,
            'numero_documento' => $event->consulta->numero_documento,
            'encontrado' => $event->consulta->encontrado,
            'nivel_riesgo' => $event->consulta->nivel_riesgo,
        ]);
    }
}
