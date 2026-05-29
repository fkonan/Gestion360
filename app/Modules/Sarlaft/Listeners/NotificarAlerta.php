<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Listeners;

use App\Models\User;
use App\Modules\Sarlaft\Events\AlertaGenerada;
use App\Modules\Sarlaft\Mail\AlertaGeneradaMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarAlerta implements ShouldQueue
{
    public const ROL_OFICIAL_CUMPLIMIENTO = 'oficial_cumplimiento';

    public function handle(AlertaGenerada $event): void
    {
        Log::channel('daily')->warning('Alerta generada', [
            'alerta_id' => $event->alerta->id,
            'intento_id' => $event->alerta->intento_id,
            'tipo' => $event->alerta->tipo,
            'nivel_riesgo' => $event->alerta->nivel_riesgo,
            'datos_persona' => $event->alerta->datos_persona,
        ]);

        $oficiales = User::role(self::ROL_OFICIAL_CUMPLIMIENTO)
            ->whereNotNull('email')
            ->get();

        if ($oficiales->isEmpty()) {
            Log::channel('daily')->warning('Alerta sin destinatarios: no hay usuarios con rol oficial_cumplimiento', [
                'alerta_id' => $event->alerta->id,
            ]);

            return;
        }

        foreach ($oficiales as $oficial) {
            try {
                Mail::to($oficial->email)->send(new AlertaGeneradaMail($event->alerta));
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Fallo envio de email a oficial de cumplimiento', [
                    'alerta_id' => $event->alerta->id,
                    'oficial_email' => $oficial->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
