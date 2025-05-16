<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Model;

class FirmaEquipajePol extends Model
{
    protected $connection = "mysql-gestion-pasajes";
    protected $table = "_firconductores";
    protected $primaryKey = "IdFirma";
    public $timestamps = false;

}
