<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class AutogestionNotificacion extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = 'autogestion_notificaciones';
  public $timestamps = false;

  protected $fillable = [
    'user_id',
    'titulo',
    'mensaje',
    'tipo',
    'modelo_rel',
    'data',
    'leida_en',
    'creada_en',
  ];

  protected $casts = [
    'data' => 'array',
    'leida_en' => 'datetime',
    'creada_en' => 'datetime',
  ];
}
