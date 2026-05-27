<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PrsTipoBloqueo extends Model
{
  protected $connection = 'mysql-gestion-admin';

  protected $table = 'prs_tipo_bloqueo';

  protected $primaryKey = 'id';

  protected $fillable = [
    'nombre',
    'codigo',
    'codigo_fics',
    'codigo_logtrans',
    'id_fics',
    'id_logtrans',
    'estado',
    'permite_levantamiento_cop',
    'handler_key',
  ];

  protected $casts = [
    'permite_levantamiento_cop' => 'boolean',
  ];

  public function campos()
  {
    return $this->hasMany(PrsTipoBloqueoCampo::class, 'tipo_bloqueo_id', 'id')
      ->where('estado', 1)
      ->orderBy('orden');
  }

  public function scopeActivos($query)
  {
    return $query->whereIn('estado', ['ACTIVO', '1']);
  }

  public function resolverHandlerKey(): ?string
  {
    if (! empty($this->handler_key)) {
      return $this->handler_key;
    }

    return match (true) {
      (int) $this->id === 11 => 'descanso',
      (int) $this->id === 35 => 'preoperacional_api',
      (string) $this->codigo === '11' => 'descanso',
      (string) $this->codigo === '35' => 'preoperacional_api',
      strtoupper((string) $this->codigo) === 'DESC' => 'descanso',
      strtoupper((string) $this->codigo) === 'PREO' => 'preoperacional_api',
      default => null,
    };
  }

  public function getCodigoFicsAttribute()
  {
    return $this->attributes['id_fics'] ?? null;
  }

  public function setCodigoFicsAttribute($value): void
  {
    $this->attributes['id_fics'] = $value;
  }

  public function getCodigoLogtransAttribute()
  {
    return $this->attributes['id_logtrans'] ?? null;
  }

  public function setCodigoLogtransAttribute($value): void
  {
    $this->attributes['id_logtrans'] = $value;
  }
}
