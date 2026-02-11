<?php

namespace App\Modules\SIG\Services;

use App\Models\User;
use App\Modules\SIG\Models\Documentos;
use App\Modules\SIG\Models\DocumentosVersiones;
use App\Notifications\EmisionEstadoNotification;
use Illuminate\Support\Facades\Log;

class NotificacionesService
{
    public function notificarCambioEstado(?int $usuarioId, string $estado, DocumentosVersiones $version): void
    {
        if (! $usuarioId) {
            return;
        }

        $creador = User::find($usuarioId);
        if (! $creador) {
            return;
        }

        $documento = Documentos::find($version->documento_id);
        $tipoSolicitud = $documento?->codigo ? 'EMISION' : 'NUEVO_DOCUMENTO';

        try {
            $creador->notify(new EmisionEstadoNotification($estado, $version, $documento, $tipoSolicitud));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar notificacion SIG', [
                'usuario_id' => $usuarioId,
                'version_id' => $version->id,
                'estado' => $estado,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
