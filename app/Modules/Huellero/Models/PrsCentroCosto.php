<?php

namespace App\Modules\Huellero\Models;

use Illuminate\Database\Eloquent\Model;

class PrsCentroCosto extends Model
{
  protected $connection = "oracle-360";
  protected $table = "PRS_CENTRO_COSTO";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;
}
