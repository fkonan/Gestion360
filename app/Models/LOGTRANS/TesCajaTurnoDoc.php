<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class TesCajaTurnoDoc extends Model
{
    protected $connection = "oracle";
    protected $table = "TES_CAJATURNODOCUMENTOS";
    protected $primaryKey = "id";
    
    public $incrementing = false;
    public $timestamps = false;

}
