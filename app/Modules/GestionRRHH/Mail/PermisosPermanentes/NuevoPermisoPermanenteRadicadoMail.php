<?php

namespace App\Modules\GestionRRHH\Mail\PermisosPermanentes;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoPermisoPermanenteRadicadoMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $datos,
        public array $adjuntos = [],
        public ?string $asunto = null
    ) {
        $this->onConnection((string) config('services.employee_permits.notifications.queue_connection', 'database-admin'));
        $this->onQueue((string) config('services.employee_permits.notifications.queue_name', 'rrhh-mails'));
    }

    public function envelope(): Envelope
    {
        $asunto = trim((string) ($this->asunto ?? ''));

        return new Envelope(
            subject: $asunto !== '' ? $asunto : 'Nuevo permiso permanente radicado'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.permisoPermanenteRadicado'
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->adjuntos as $adjunto) {
            if (! is_array($adjunto)) {
                continue;
            }

            $nombre = trim((string) ($adjunto['name'] ?? ''));
            $mime = trim((string) ($adjunto['mime'] ?? ''));
            $contenidoBase64 = trim((string) ($adjunto['data_base64'] ?? ''));
            $contenido = $contenidoBase64 !== '' ? base64_decode($contenidoBase64, true) : ($adjunto['data'] ?? null);
            if (! is_string($contenido) || $contenido === '') {
                continue;
            }

            if ($nombre === '') {
                $nombre = 'adjunto';
            }

            $attachment = Attachment::fromData(
                fn () => $contenido,
                $nombre
            );

            if ($mime !== '') {
                $attachment = $attachment->withMime($mime);
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
