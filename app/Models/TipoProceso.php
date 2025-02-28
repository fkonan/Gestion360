<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoProceso extends Model
{
    protected $table = "_tipo_proceso";
    protected $primaryKey = "Id";
    protected $fillable = ['Id', 'Nombre', 'Nivel'];

    public $timestamps = false;
}
