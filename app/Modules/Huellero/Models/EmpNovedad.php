<?php

namespace App\Modules\Huellero\Models;

use Illuminate\Database\Eloquent\Model;

class EmpNovedad extends Model
{
  protected $connection = 'oracle-360';
  protected $table = 'EMP_NOVEDADES';
  protected $primaryKey = 'id';
  public $incrementing = false;
  protected $keyType = 'string';
  public $timestamps = false;
}

