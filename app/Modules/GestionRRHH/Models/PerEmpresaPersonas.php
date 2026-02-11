<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PerEmpresaPersonas extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_EMPRESAPERSONAS";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = false;

    public function perPersona()
    {
        return $this->belongsTo(PerPersonas::class, 'pe_id_emp', 'id');
    }
}
