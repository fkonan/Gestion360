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
        "usrcreacion","empcreacion","tiporegistro",
        "observacion"
    ];

    public function PerContratoPersona(){
        return $this->hasOne(PerContratoPersona::class,'pe_id_pe','pe_id')
                ->where('estborrado',0)
                ->where('estado',1);
    }

    public function PerPersona(){
        return $this->hasOne(PerPersonas::class,'id','pe_id')
                ->where('estborrado',0)
                ->where('estado','ACTIVO');
    }

   /*  public function agenciaCreacion(){
        return $this->belongsTo(PerPersonas::class,'id','empcreacion')
                ->where('estborrado',0)
                ->where('estado','ACTIVO');
    } */
}
