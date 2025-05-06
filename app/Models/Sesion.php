<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Sesion extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = "_sesion";
    protected $primaryKey = "IdSesion"; 
    public $timestamps = false;
    
    protected $fillable = ['IdUser', 'IdUser', 'SesionFechReg', 'SesionHorReg', 'SesionTipo'];

    public function user():BelongsTo{
        return $this->BelongsTo(User::class,'IdUser','IdUsuario'); 
    }


}
