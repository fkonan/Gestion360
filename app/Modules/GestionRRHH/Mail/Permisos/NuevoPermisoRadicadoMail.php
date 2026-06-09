<?php

namespace App\Modules\GestionRRHH\Mail\Permisos;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoPermisoRadicadoMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $datos,
        public ?string $pdfContenido = null,
        public ?string $pdfContenidoBase64 = null,
        public ?string $pdfNombre = null,
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
            subject: $asunto !== '' ? $asunto : 'Nuevo permiso radicado'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.permisoRadicado'
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        $pdfContenido = null;
        $pdfContenidoBase64 = trim((string) ($this->pdfContenidoBase64 ?? ''));
        if ($pdfContenidoBase64 !== '') {
            $decoded = base64_decode($pdfContenidoBase64, true);
            if (is_string($decoded) && $decoded !== '') {
                $pdfContenido = $decoded;
            }
        }

        if ($pdfContenido === null && $this->pdfContenido !== null && $this->pdfContenido !== '') {
            $pdfContenido = $this->pdfContenido;
        }

        if (is_string($pdfContenido) && $pdfContenido !== '') {
            $nombreArchivo = trim((string) ($this->pdfNombre ?? ''));
            if ($nombreArchivo === '') {
                $nombreArchivo = 'permiso-empleado-radicado.pdf';
            }

            $attachments[] = Attachment::fromData(
                fn () => $pdfContenido,
                $nombreArchivo
            )->withMime('application/pdf');
        }

        foreach ($this->adjuntos as $adjunto) {
            if (! is_array($adjunto)) {
                continue;
            }

            $nombre = trim((string) ($adjunto['name'] ?? ''));
            $mime = trim((string) ($adjunto['mime'] ?? ''));
            $contenidoBase64 = trim((string) ($adjunto['data_base64'] ?? ''));
            $contenido = $contenidoBase64 !== '' ? base64_decode($contenidoBase64, true) : ($adjunto['data'] ?? null);
            if (is_string($contenido) && $contenido !== '') {
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
                continue;
            }

            $path = trim((string) ($adjunto['path'] ?? ''));
            if ($path === '' || ! is_file($path)) {
                continue;
            }

            if ($nombre === '') {
                $nombre = basename($path);
            }

            $attachment = Attachment::fromPath($path)->as($nombre);
            if ($mime !== '') {
                $attachment = $attachment->withMime($mime);
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
