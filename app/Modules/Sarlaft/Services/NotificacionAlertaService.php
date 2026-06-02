<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Models\User;
use App\Modules\Sarlaft\Mail\ResumenAlertasMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificacionAlertaService
{
    public const ROL_OFICIAL_CUMPLIMIENTO = 'oficial_cumplimiento';

    /**
     * Envia UN solo correo resumen a los oficiales de cumplimiento con las
     * alertas generadas en una corrida. Si no hay alertas, no hace nada.
     *
     * @param  Collection<int, \App\Modules\Sarlaft\Models\Alerta>  $alertas
     */
    public function notificarResumen(Collection $alertas, string $origen): void
    {
        if ($alertas->isEmpty()) {
            return;
        }

        // El correo del usuario corporativo vive en la persona vinculada:
        // User -> persona -> datos -> PerEmail (tabla _personas_datos).
        $oficiales = User::role(self::ROL_OFICIAL_CUMPLIMIENTO)
            ->with('persona.datos')
            ->get();

        $destinatarios = $oficiales
            ->map(static fn (User $oficial): ?string => $oficial->persona?->datos?->PerEmail)
            ->filter(static fn (?string $email): bool => is_string($email) && trim($email) !== '')
            ->unique()
            ->values()
            ->all();

        if ($destinatarios === []) {
            Log::channel('daily')->warning('SARLAFT: resumen de alertas sin destinatarios (oficiales de cumplimiento sin correo en su persona)', [
                'origen' => $origen,
                'total_alertas' => $alertas->count(),
            ]);

            return;
        }

        try {
            Mail::to($destinatarios)->send(new ResumenAlertasMail($alertas, $origen));
        } catch (\Throwable $e) {
            Log::channel('daily')->error('SARLAFT: fallo el envio del correo resumen de alertas', [
                'origen' => $origen,
                'total_alertas' => $alertas->count(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
