<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubModulo extends Model
{
    protected $table = "_submodulos";
    protected $primaryKey = "IdSubModulo";
    protected $fillable = ["ModuloId", "SubModNom", "SubModDes", "SubModuloEstado","SubModFecReg","SubModHoReg"];
    public $timestamps = false; 
}
