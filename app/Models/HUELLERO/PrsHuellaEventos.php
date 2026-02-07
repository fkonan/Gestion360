<?php

namespace App\Models\HUELLERO;

use Illuminate\Database\Eloquent\Model;

class PrsHuellaEventos extends Model
{
  protected $connection = "oracle-360";
  protected $table = "PRS_HUELLA_EVENTOS";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;
}
