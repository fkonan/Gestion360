<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Model;

class FirmaPoliticas extends Model
{
    protected $connection = "mysql-gestion-pasajes";
    protected $table = "_FirConductores";
    protected $primaryKey = "IdFirma";
    public $timestamps = false;

}


