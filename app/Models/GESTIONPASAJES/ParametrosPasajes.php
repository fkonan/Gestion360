<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Model;

class ParametrosPasajes extends Model
{
  protected $connection = 'mysql-gestion-pasajes';
  protected $table = '_parametros';
  protected $primaryKey = 'IdParametro';
  public $timestamps = false;

  public static function getDescansoConductores()
  {
    return self::where('ParNomGru', 'DESCANSO CONDUCTORES')->get();
  }
}
