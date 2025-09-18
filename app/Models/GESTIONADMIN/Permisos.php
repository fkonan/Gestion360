<?php

namespace App\Models\GESTIONADMIN;

use Spatie\Permission\Models\Permission;

class Permisos extends Permission
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = 'permisos';
  protected $primaryKey = 'id';
  public $timestamps = false;

  public function modulo()
  {
    return $this->belongsTo(Modulo::class, 'ModuloId', 'IdModulo');
  }
}
