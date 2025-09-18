<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class PerBloqueoConNov extends Model
{
  protected $connection = "oracle";
  protected $table = "PER_BLOQUEOCONDUCNOV";
  protected $primaryKey = "id";
  public $incrementing = false;
  protected $keyType = 'string';

  public $timestamps = false;

  protected $fillable = [
    "fecha_fin"
  ];
}
