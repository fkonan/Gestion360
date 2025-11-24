<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class FitTipoVehiculos extends Model
{
  protected $connection = "oracle";
  protected $table = "FIT_TIPOVEHICULOS";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;

  protected $fillable = [
    'servicio',
    'descripcion'
  ];
}
