<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class ConDetCarguePagRec extends Model
{
    protected $connection = "oracle";
    protected $table = "CON_DETCARGUEPAGOSYRECAUDOS";
    protected $primaryKey = "id";
    public $incrementing = false;
    
    public $timestamps = false;

     protected $fillable = [
        "id","id_carguepagyrec","concepto","iden_clienteprincipal","clienteprincipal",
        "iden_clientesecundario","clientesecundario","valortotal","valorparcial","estado",
        "codciudad","ciudad","fecha_desde","fecha_hasta","nro_interno","codagencia",
        "agencia","estborrado","fecmodifica","empmodifica","usrmodifica","rolmodifica",
        "feccreacion","usrcreacion","empcreacion"
    ];

}
