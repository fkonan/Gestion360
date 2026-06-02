<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ResumenAlertasMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  Collection<int, \App\Modules\Sarlaft\Models\Alerta>  $alertas
     */
    public function __construct(
        public readonly Collection $alertas,
        public readonly string $origen,
    ) {}

    public function envelope(): Envelope
    {
        $total = $this->alertas->count();
        $vinculantes = $this->alertas
            ->filter(fn ($a): bool => in_array(strtolower(trim((string) $a->nivel_riesgo)), ['vinculante', 'alto'], true))
            ->count();

        $sufijo = $vinculantes > 0 ? " ({$vinculantes} en lista vinculante)" : '';

        return new Envelope(
            subject: "[SARLAFT] {$total} nueva(s) coincidencia(s){$sufijo}",
        );
    }

    public function content(): Content
    {
        $porDocumento = $this->alertas
            ->groupBy('numero_documento')
            ->map(fn (Collection $grupo): array => [
                'numero_documento' => $grupo->first()->numero_documento,
                'nombre' => $grupo->first()->datos_persona['nombre'] ?? '-',
                'cantidad' => $grupo->count(),
                'nivel' => strtolower(trim((string) $grupo->first()->nivel_riesgo)),
            ])
            ->values();

        return new Content(
            view: 'sarlaft::emails.resumen-alertas',
            with: [
                'total' => $this->alertas->count(),
                'origen' => $this->origen,
                'porDocumento' => $porDocumento,
            ],
        );
    }
}
