<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PerCargos extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_CARGOS";
    protected $primaryKey = "codigo";
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    public function perContratoPersona()
    {
        return $this->hasOne(PerContratoPersona::class, 'cargo', 'codigo')
                ->where('estborrado', 0)
                ->where('estado', 1);
    }
}
