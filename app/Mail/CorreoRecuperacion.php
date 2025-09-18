<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoRecuperacion extends Mailable
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
      subject: 'Enlace de Recuperación de Contraseña',
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.recuperacion',
    );
  }

  public function attachments(): array
  {
    return [];
  }
}
