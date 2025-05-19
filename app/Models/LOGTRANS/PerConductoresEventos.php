<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class PerConductoresEventos extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_CONDUCTORESEVENTOS";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        "pe_id","fechaevento","evento","anotacion",
        "fecmodifica","usrmodifica","rolmodifica",
        "empmodifica","estborrado","feccreacion",
        "usrcreacion","empcreacion","tiporegistro"
    ];
}
