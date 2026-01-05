<?php

namespace App\Notifications\Channels;

use App\Models\GESTIONADMIN\AutogestionNotificacion;
use App\Notifications\Contracts\AutogestionNotification;
use Illuminate\Notifications\Notification;

class AutogestionDatabaseChannel
{
  /**
   * @param mixed $notifiable
   * @param Notification $notification
   */
  public function send($notifiable, Notification $notification): void
  {
    if (!$notification instanceof AutogestionNotification) {
      return;
    }

    /** @var AutogestionNotification $notification */
    $data = $notification->toAutogestion($notifiable);
    AutogestionNotificacion::create([
      'user_id' => $notifiable?->getKey(),
      'titulo' => $data['titulo'] ?? 'Notificacion',
      'mensaje' => $data['mensaje'] ?? '',
      'tipo' => $data['tipo'] ?? null,
      'modelo_rel' => $data['modelo_rel'] ?? null,
      'data' => $data['data'] ?? null,
      'creada_en' => now(),
    ]);
  }
}
