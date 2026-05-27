<?php

namespace App\Modules\Huellero\Models;

use Illuminate\Database\Eloquent\Model;

class PrsHorariosCargos extends Model
{
  protected $connection = "oracle-360";
  protected $table = "PRS_HORARIOS_CARGOS";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;
}
