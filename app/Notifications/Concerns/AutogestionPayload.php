<?php

namespace App\Notifications\Concerns;

trait AutogestionPayload
{
  protected function buildAutogestionPayload(array $payload): array
  {
    $defaults = [
      'titulo' => 'Notificacion',
      'mensaje' => '',
      'tipo' => null,
      'modelo_rel' => null,
      'data' => [],
    ];

    $merged = array_merge($defaults, $payload);
    $merged['data'] = array_merge($defaults['data'], $payload['data'] ?? []);

    return $merged;
  }
}
