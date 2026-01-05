<?php

namespace App\Notifications;

use App\Models\GESTIONADMIN\SIG\Documentos;
use App\Models\GESTIONADMIN\SIG\DocumentosVersiones;
use App\Notifications\Channels\AutogestionDatabaseChannel;
use Illuminate\Notifications\Notification;
use App\Notifications\Contracts\AutogestionNotification;

class EmisionEstadoNotification extends Notification implements AutogestionNotification
{
  public function __construct(
    private string $estado,
    private DocumentosVersiones $version,
    private ?Documentos $documento = null
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

    return [
      'titulo' => 'Emision actualizada',
      'mensaje' => "El documento {$codigo} {$nombre} cambio a estado {$this->estado}",
      'tipo' => 'emision',
      'modelo_rel' => 'DocumentosVersiones',
      'data' => [
        'estado' => $this->estado,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'version' => $this->version->version,
      ],
    ];
  }
}
