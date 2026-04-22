<?php

namespace App\Services\Asistencia;

class DecisionEventoDTO
{
  public const STATUS_OK = 'OK';
  public const STATUS_RECHAZADO = 'RECHAZADO';

  public function __construct(
    public readonly string $status,
    public readonly ?int $evento,
    public readonly ?string $motivo,
    public readonly ?int $horarioCargoId,
    public readonly ?int $cargoId,
    public readonly bool $llegadaTarde,
    public readonly bool $cargoEspecial,
    public readonly ?string $descripcion,
    public readonly array $trace = []
  ) {
  }

  public static function ok(
    int $evento,
    ?int $horarioCargoId,
    ?int $cargoId,
    bool $llegadaTarde = false,
    bool $cargoEspecial = false,
    ?string $descripcion = null,
    array $trace = []
  ): self {
    return new self(
      self::STATUS_OK,
      $evento,
      null,
      $horarioCargoId,
      $cargoId,
      $llegadaTarde,
      $cargoEspecial,
      $descripcion,
      $trace
    );
  }

  public static function rechazado(
    string $motivo,
    ?int $cargoId,
    bool $cargoEspecial = false,
    array $trace = []
  ): self
  {
    return new self(
      self::STATUS_RECHAZADO,
      null,
      $motivo,
      null,
      $cargoId,
      false,
      $cargoEspecial,
      null,
      $trace
    );
  }

  public function esOk(): bool
  {
    return $this->status === self::STATUS_OK;
  }

  public function toArray(): array
  {
    return [
      'status' => $this->status,
      'evento' => $this->evento,
      'motivo' => $this->motivo,
      'horario_cargo_id' => $this->horarioCargoId,
      'cargo_id' => $this->cargoId,
      'flags' => [
        'llegada_tarde' => $this->llegadaTarde,
        'cargo_especial' => $this->cargoEspecial,
      ],
      'descripcion' => $this->descripcion,
      'trace' => $this->trace,
    ];
  }
}
