<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class ActualizacionDatos extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = 'otro_si';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
