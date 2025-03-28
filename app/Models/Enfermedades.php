<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enfermedades extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = "enfermedades";
    protected $primaryKey = "IdEnfermedad";

    public $timestamps = false;

    public function usuarioRegistro(): BelongsTo{
        return $this->belongsTo(User::class,'IdUserReg','IdUsuario');
    }
}
