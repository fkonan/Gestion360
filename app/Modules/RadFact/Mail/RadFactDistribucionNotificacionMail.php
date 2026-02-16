<?php

namespace App\Modules\RadFact\Mail;

use App\Modules\RadFact\Models\RadFactRadicacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RadFactDistribucionNotificacionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public RadFactRadicacion $radicacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva factura radicada para aprobación',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'radfact::emails.radicacion-distribuida',
            with: [
                'title' => 'Notificación de Radicación',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
