<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncapacidadesDocumentos extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = "incapacidades_documentos";
    protected $primaryKey = "IdDocumento";

    public $timestamps = false;

    public function incapacidad(){
        return $this->belongsTo(Incapacidad::class, 'IncapacidadId', 'IdIncapacidad');
    }

}
