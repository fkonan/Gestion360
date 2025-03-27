<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class incapacidadesSeguimiento extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = 'incapacidades_seguimiento';
    protected $primaryKey = 'idSeguimiento';
    public $timestamps = false;
}
