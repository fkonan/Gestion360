<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funciones extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = "_funciones";
    protected $primaryKey = "IdFunciones";

    protected $fillable = [
        'FunNom', 'FunDes','FuncionEstado',
        'FunTip','FunFecReg','FunHoReg'
    ];

    public $timestamps = false;
}
