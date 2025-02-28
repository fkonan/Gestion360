<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocProceso extends Model
{
    protected $table = "_tipo_doc_procesos";
    protected $primaryKey = "Id";
    protected $fillable = ['Id', 'Nombre'];

    public $timestamps = false;

    
}
