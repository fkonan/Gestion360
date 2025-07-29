<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class Notificaciones extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = "_notificaciones";
    protected $primaryKey = "id";

    protected $fillable = [
        'id','titulo','bodyPush','bodyCompleto','imagen',
        'datos','destino','estado','privacidad','proceso',
        'programada','createdAt','createdBy', 'usuarioCrea'
    ];

    public $timestamps = false;
}
