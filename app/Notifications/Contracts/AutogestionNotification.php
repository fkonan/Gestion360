<?php

namespace App\Notifications\Contracts;

interface AutogestionNotification
{
  /**
   * Retorna los datos a guardar en autogestion_notificaciones.
   *
   * @param mixed $notifiable
   * @return array<string,mixed>
   */
  public function toAutogestion($notifiable): array;
}
