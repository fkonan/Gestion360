<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class UsuarioTemporal extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = 'usuarios_temporales';
  protected $primaryKey = 'id';

  protected $fillable = [
    'correo',
    'token',
    'nombreCompleto',
    'identificacion',
    'estado',
  ];

  protected $casts = [
    'estado' => 'boolean'
  ];
}
