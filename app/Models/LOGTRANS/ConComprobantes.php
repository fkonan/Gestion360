<?php

namespace App\Models\LOGTRANS;

use Illuminate\Database\Eloquent\Model;

class ConComprobantes extends Model
{
    protected $connection = "oracle";
    protected $table = "CON_COMPROBANTES";
    protected $primaryKey = "id";
    public $incrementing = false;
   
    public $timestamps = false;

     protected $fillable = [
       
    ];

}
