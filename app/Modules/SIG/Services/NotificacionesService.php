<?php

namespace App\Modules\SIG\Services;

use App\Models\GESTIONADMIN\Notificaciones;
use App\Models\User;
use App\Modules\SIG\Models\Documentos;
use App\Modules\SIG\Models\DocumentosVersiones;
use App\Notifications\EmisionEstadoNotification;
use Illuminate\Support\Facades\Log;

class NotificacionesService
{
    private const USUARIO_ADMIN_PRUEBA_ID = 2;

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

    public function enviarPushSolicitudPendienteRevision(DocumentosVersiones $version): void
    {
        $documento = Documentos::find($version->documento_id);
        $tipoSolicitud = $documento?->codigo ? 'EMISION' : 'NUEVO_DOCUMENTO';
        $etiqueta = $tipoSolicitud === 'NUEVO_DOCUMENTO' ? 'documento nuevo' : 'emision';
        $detalleDocumento = trim(($documento?->codigo ?? '').' '.($documento?->nombre ?? ''));
        $detalleDocumento = $detalleDocumento !== '' ? $detalleDocumento : 'sin codigo';
        $destino = json_encode(['usuarios' => [self::USUARIO_ADMIN_PRUEBA_ID]]);
        $bodyPush = "Nueva solicitud de {$etiqueta} para {$detalleDocumento}.";
        $comentario = trim((string) ($version->comentario_revision ?? ''));
        $bodyCompleto = "Se registro una nueva solicitud SIG de {$etiqueta} para el documento {$detalleDocumento} y quedo pendiente de revision. Para ver mas detalle, ingresa a la plataforma de Autogestion.";

        if ($comentario !== '') {
            $bodyCompleto .= "\n\nComentario del solicitante:\n".$comentario;
        }

        try {
            Notificaciones::create([
                'titulo' => 'Nueva solicitud SIG',
                'bodyPush' => $bodyPush,
                'bodyCompleto' => $bodyCompleto,
                'usuarioCrea' => $version->usrcreacion ?? auth()->user()?->IdUsuario ?? null,
                'destino' => $destino,
                'privacidad' => 'privada',
                'createdBy' => 'Gestion360',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar push SIG al administrador', [
                'destinatario_id' => self::USUARIO_ADMIN_PRUEBA_ID,
                'version_id' => $version->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
