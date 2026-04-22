<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class FuncionesPasajes extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = '_funciones';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
