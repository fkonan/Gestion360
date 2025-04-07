<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametros extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = '_parametros';
    protected $primaryKey = 'IdParametro';
    public $timestamps = false;

    public function causasIncapacidad(){
        return $this->hasMany(Incapacidad::class, 'CausaId', 'IdParametro')
            ->where('ParNomGru','CAUSA-INCAPACIDAD');
    }

    public function documentosIncapacidad(){
        return $this->hasMany(IncapacidadesDocumentos::class, 'ParametroId', 'IdParametro')
            ->whereIn('ParNomGru',['DOCUMENTOS-INCAPACIDAD','DOCUMENTOS-INCAPACIDAD-MP','DOCUMENTOS-INCAPACIDAD-AT']);
    }
}
