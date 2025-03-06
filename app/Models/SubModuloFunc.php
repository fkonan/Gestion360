<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubModuloFunc extends Model
{
    protected $table = "_submodulos_funciones";
    protected $primaryKey = "IdSubModFun";
    protected $fillable = [
        'SubModuloId', 'FuncionId', 'FunFecReg', 'FunHorReg'
    ];
    public $timestamps = false;

    
}
