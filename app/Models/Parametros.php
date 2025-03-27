<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametros extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = '_parametros';
    protected $primaryKey = 'IdParametro';
    public $timestamps = false;

    public function incapacidades(){
        return $this->hasMany(Incapacidad::class, 'CausaId', 'IdParametro')
            ->where('ParNomGru','CAUSA-INCAPACIDAD');
    }

}
