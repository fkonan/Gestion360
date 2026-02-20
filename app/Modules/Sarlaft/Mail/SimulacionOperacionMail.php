<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Mail;

use App\Modules\Sarlaft\Models\Consulta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SimulacionOperacionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $datosOperacion
     */
    public function __construct(
        public readonly string $escenario,
        public readonly array $datosOperacion,
        public readonly Consulta $consulta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SARLAFT Simulacion '.$this->escenario.' - Riesgo '.strtoupper((string) $this->consulta->nivel_riesgo),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sarlaft::emails.simulacion-operacion',
            with: [
                'escenario' => $this->escenario,
                'datosOperacion' => $this->datosOperacion,
                'consulta' => $this->consulta,
            ],
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
