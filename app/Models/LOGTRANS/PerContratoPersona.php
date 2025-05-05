<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class PerContratoPersona extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_CONTRATO_PERSONA";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;
}
