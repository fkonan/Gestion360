<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class ConDetPagoRecaudo extends Model
{
    protected $connection = "oracle";
    protected $table = "CON_DETALLEPAGORECAUDO";
    protected $primaryKey = "id";
    public $incrementing = false;
    
    public $timestamps = false;
}
