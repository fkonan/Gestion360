<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoUsuarioTemporal extends Mailable 
{
    use Queueable, SerializesModels;

    public $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Usuario temporal plataforma gestión',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.usuarioTemporal',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
