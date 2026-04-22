<?php

namespace App\Notifications;

use App\Modules\SIG\Models\Documentos;
use App\Modules\SIG\Models\DocumentosVersiones;
use App\Notifications\Channels\AutogestionDatabaseChannel;
use App\Notifications\Concerns\AutogestionPayload;
use App\Notifications\Contracts\AutogestionNotification;
use Illuminate\Notifications\Notification;

class EmisionEstadoNotification extends Notification implements AutogestionNotification
{
  use AutogestionPayload;

  public function __construct(
    private string $estado,
    private DocumentosVersiones $version,
    private ?Documentos $documento = null,
    private string $tipoSolicitud = 'EMISION'
  ) {
  }

  public function via($notifiable): array
  {
    return [AutogestionDatabaseChannel::class];
  }

  public function toAutogestion($notifiable): array
  {
    $codigo = $this->documento?->codigo ?? '';
    $nombre = $this->documento?->nombre ?? '';
    $etiqueta = $this->tipoSolicitud === 'NUEVO_DOCUMENTO' ? 'documento nuevo' : 'emision';
    $detalleDocumento = trim("{$codigo} {$nombre}");
    $detalleDocumento = $detalleDocumento !== '' ? $detalleDocumento : 'sin codigo';

    return $this->buildAutogestionPayload([
      'titulo' => 'Solicitud SIG actualizada',
      'mensaje' => "La solicitud de {$etiqueta} del documento {$detalleDocumento} cambio a estado {$this->estado}",
      'tipo' => 'sig_solicitud',
      'modelo_rel' => 'DocumentosVersiones',
      'data' => [
        'estado' => $this->estado,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'version' => $this->version->version,
        'tipo_solicitud' => $this->tipoSolicitud,
        'url_destino' => route('sig.mis-solicitudes.index'),
      ],
    ]);
  }
}
