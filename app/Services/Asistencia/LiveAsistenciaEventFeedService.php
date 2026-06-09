<?php

namespace App\Services\Asistencia;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LiveAsistenciaEventFeedService
{
  private const CACHE_SEQ_KEY = 'asistencia:eventos:live:seq';
  private const CACHE_ITEM_PREFIX = 'asistencia:eventos:live:item:';
  private const CACHE_TTL_MINUTES = 10;
  private const MAX_BATCH = 100;

  public function latestId(): int
  {
    return max(0, (int) Cache::get(self::CACHE_SEQ_KEY, 0));
  }

  public function publish(array $payload): int
  {
    $nextId = (int) Cache::increment(self::CACHE_SEQ_KEY);
    if ($nextId <= 0) {
      $nextId = max(1, $this->latestId());
      Cache::forever(self::CACHE_SEQ_KEY, $nextId);
    }

    $item = $this->normalizePayload($payload, $nextId);
    Cache::put($this->eventKey($nextId), $item, now()->addMinutes(self::CACHE_TTL_MINUTES));

    return $nextId;
  }

  public function pullAfter(int $lastId, int $limit = 50): array
  {
    $cursor = max(0, $lastId);
    $safeLimit = max(1, min(self::MAX_BATCH, $limit));
    $latestId = $this->latestId();

    if ($latestId <= $cursor) {
      return [
        'latest_id' => $latestId,
        'events' => [],
      ];
    }

    $events = [];
    for ($id = $cursor + 1; $id <= $latestId; $id++) {
      $cached = Cache::get($this->eventKey($id));
      if (!is_array($cached)) {
        continue;
      }

      $cached['stream_id'] = (int) ($cached['stream_id'] ?? $id);
      $events[] = $cached;

      if (count($events) >= $safeLimit) {
        break;
      }
    }

    return [
      'latest_id' => $latestId,
      'events' => $events,
    ];
  }

  private function eventKey(int $id): string
  {
    return self::CACHE_ITEM_PREFIX . $id;
  }

  private function normalizePayload(array $payload, int $streamId): array
  {
    $identificacion = trim((string) ($payload['identificacion'] ?? ''));
    $nombre = trim((string) ($payload['nombre'] ?? ''));
    $eventoId = isset($payload['evento_id']) ? trim((string) $payload['evento_id']) : null;
    $evento = (int) ($payload['evento'] ?? 0);
    $descripcion = trim((string) ($payload['descripcion'] ?? ''));
    $origen = trim((string) ($payload['origen'] ?? 'api'));

    $fechaEvento = null;
    if (!empty($payload['fecha_evento'])) {
      try {
        $fechaEvento = Carbon::parse((string) $payload['fecha_evento']);
      } catch (\Throwable $e) {
        $fechaEvento = null;
      }
    }

    $horaEvento = trim((string) ($payload['hora_evento'] ?? ''));
    if ($horaEvento === '' && $fechaEvento instanceof Carbon) {
      $horaEvento = $fechaEvento->format('g:i a');
    }

    return [
      'stream_id' => $streamId,
      'evento_id' => $eventoId !== '' ? $eventoId : null,
      'identificacion' => $identificacion,
      'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
      'evento' => in_array($evento, [1, 2], true) ? $evento : 0,
      'descripcion' => $descripcion,
      'fecha_evento' => $fechaEvento ? $fechaEvento->toIso8601String() : null,
      'hora_evento' => $horaEvento !== '' ? $horaEvento : null,
      'origen' => $origen !== '' ? $origen : 'api',
    ];
  }
}
