<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class RolApp extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = '_roles';
  protected $primaryKey = 'IdRol';
  public $timestamps = false;

  protected $fillable = [
    'IdRol',
    'IdUser',
    'RolSocio',
    'RolEmp',
    'RolCli',
    'Movil',
    'Web',
    'RolFecReg',
    'RolHorReg',
  ];
}
