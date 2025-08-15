<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Model;

class Funciones extends Model
{
    protected $connection = "mysql-gestion-pasajes";
    protected $table = "_funciones";
    protected $primaryKey = "id";
    public $timestamps = false;
}
