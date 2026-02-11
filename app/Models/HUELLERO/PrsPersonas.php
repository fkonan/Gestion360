<?php

namespace App\Models\HUELLERO;

use Illuminate\Database\Eloquent\Model;

class PrsPersonas extends Model
{
  protected $connection = "oracle-360";
  protected $table = "PRS_PERSONAS";
  protected $primaryKey = "id";
  public $incrementing = false;

  public $timestamps = false;
}
