<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PrsTipoBloqueo extends Model
{
  protected $connection = 'mysql-gestion-admin';

  protected $table = 'prs_tipo_bloqueo';

  protected $primaryKey = 'id';

  protected $fillable = [
    'nombre',
    'codigo',
    'codigo_fics',
    'codigo_logtrans',
    'estado',
    'permite_levantamiento_cop'
  ];
}
