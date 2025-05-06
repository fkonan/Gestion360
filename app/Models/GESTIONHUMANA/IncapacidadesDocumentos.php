<?php

namespace App\Models\GESTIONHUMANA;

use App\Models\Parametros;
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

    public function tipoDocumento(){
        return $this->belongsTo(Parametros::class, 'ParametroId', 'IdParametro')
            ->whereIn('ParNomGru',['DOCUMENTOS-INCAPACIDAD','DOCUMENTOS-INCAPACIDAD-MP','DOCUMENTOS-INCAPACIDAD-AT']);
    }
}
