<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class PerPersonas extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_PERSONAS";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = false;

    public function PerContratoPersona(){
        return $this->hasOne(PerContratoPersona::class,'pe_id_pe','id')
                ->where('estborrado',0)
                ->where('estado',1);
    }
}
