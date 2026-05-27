<?php

namespace App\Modules\GestionRRHH\Mail\Descargos;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevaCitacionDescargosMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public array $datos)
    {
        $this->onConnection((string) config('services.employee_permits.notifications.queue_connection', 'database-admin'));
        $this->onQueue((string) config('services.employee_permits.notifications.queue_name', 'rrhh-mails'));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Citacion a descargos'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.citacionDescargos'
        );
    }
}

