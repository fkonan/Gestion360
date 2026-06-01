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
        $nivel = strtolower(trim((string) $this->alerta->nivel_riesgo));

        return match (true) {
            in_array($nivel, ['vinculante', 'alto']) => '[SARLAFT] Coincidencia en Lista Vinculante',
            in_array($nivel, ['restrictiva', 'medio']) => '[SARLAFT] Coincidencia en Lista Restrictiva',
            default => '[SARLAFT] Nueva coincidencia registrada',
        };
    }

    private function resolverColorNivel(): string
    {
        $nivel = strtolower(trim((string) $this->alerta->nivel_riesgo));

        return match (true) {
            in_array($nivel, ['vinculante', 'alto']) => '#dc3545',
            in_array($nivel, ['restrictiva', 'medio']) => '#6c757d',
            default => '#6c757d',
        };
    }

    private function resolverEtiquetaNivel(): string
    {
        $nivel = strtolower(trim((string) $this->alerta->nivel_riesgo));

        return match (true) {
            in_array($nivel, ['vinculante', 'alto']) => 'LISTA VINCULANTE',
            in_array($nivel, ['restrictiva', 'medio']) => 'LISTA RESTRICTIVA',
            default => 'COINCIDENCIA',
        };
    }
}
