<?php

namespace App\Modules\SIG\Models;

use Illuminate\Database\Eloquent\Model;

class Documentos extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = 'sig_documentos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function proceso()
    {
        return $this->belongsTo(Procesos::class, 'id_proceso');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TiposDocumentos::class, 'id_tipo_doc');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicaciones::class, 'id_ubicacion');
    }

    public function versiones()
    {
        return $this->hasMany(DocumentosVersiones::class, 'documento_id');
    }
}
