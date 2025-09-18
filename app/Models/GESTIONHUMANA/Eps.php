<?php

namespace App\Models\GESTIONHUMANA;

use Illuminate\Database\Eloquent\Model;

class Eps extends Model
{
  protected $connection = 'mysql-gestion-humana';
  protected $table = "eps";
  protected $primaryKey = "IdEPS";

  public $timestamps = false;

  public function incapacidadesAsociadas()
  {
    return $this->hasMany(Incapacidad::class, 'EPSId', 'IdEPS');
  }
}
