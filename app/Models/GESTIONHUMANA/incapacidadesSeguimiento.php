<?php

namespace App\Models\GESTIONHUMANA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class incapacidadesSeguimiento extends Model
{
  protected $connection = 'mysql-gestion-humana';
  protected $table = 'incapacidades_seguimiento';
  protected $primaryKey = 'idSeguimiento';
  public $timestamps = false;

  protected $fillable = [
    "IncapacidadId",
    "Observacion",
    "SegFecReg",
    "SegHoReg",
    "UserRegistra",
    "Estado"
  ];

  public function incapacidad(): BelongsTo
  {
    return $this->belongsTo(Incapacidad::class, 'IncapacidadId', 'IdIncapacidad');
  }
}
