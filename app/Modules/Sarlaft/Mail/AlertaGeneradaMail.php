<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Mail;

use App\Modules\Sarlaft\Models\Alerta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaGeneradaMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Alerta $alerta,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolverSubject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sarlaft::emails.alerta-generada',
            with: [
                'alerta' => $this->alerta,
                'nivel' => $this->alerta->nivel_riesgo,
                'colorNivel' => $this->resolverColorNivel(),
                'etiquetaNivel' => $this->resolverEtiquetaNivel(),
            ],
        );
    }

    private function resolverSubject(): string
    {
        return match ($this->alerta->nivel_riesgo) {
            'alto' => '[SARLAFT] ALERTA CRITICA - Coincidencia confirmada',
            'medio' => '[SARLAFT] Alerta de revision - Multiples coincidencias',
            'bajo' => '[SARLAFT] Alerta para revision - Coincidencia menor',
            default => '[SARLAFT] Nueva alerta generada',
        };
    }

    private function resolverColorNivel(): string
    {
        return match ($this->alerta->nivel_riesgo) {
            'alto' => '#dc3545',
            'medio' => '#fd7e14',
            'bajo' => '#ffc107',
            default => '#6c757d',
        };
    }

    private function resolverEtiquetaNivel(): string
    {
        return match ($this->alerta->nivel_riesgo) {
            'alto' => 'CRITICA',
            'medio' => 'MEDIA',
            'bajo' => 'BAJA',
            default => 'INFORMATIVA',
        };
    }
}
