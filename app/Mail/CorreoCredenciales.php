<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CorreoCredenciales extends Mailable
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
      subject: 'Credenciales Plataforma Gestión',
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.credenciales',
    );
  }

  public function attachments(): array
  {
    return [];
  }
}
