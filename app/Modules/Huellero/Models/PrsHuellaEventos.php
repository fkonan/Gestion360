<?php

namespace App\Modules\Huellero\Models;

use Illuminate\Database\Eloquent\Model;

class PrsHuellaEventos extends Model
{
  protected $connection = "oracle-360";
  protected $table = "PRS_EVENTOS";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;
}
