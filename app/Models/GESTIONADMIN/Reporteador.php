<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class Reporteador extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = 'reporteador';
  protected $primaryKey = 'id';
  public $timestamps = false;

  protected $fillable = [
    'id',
    'nombre',
    'descripcion',
    'sql_base',
    'parametros',
    'origen_db',
    'total_consultas'
  ];
}
