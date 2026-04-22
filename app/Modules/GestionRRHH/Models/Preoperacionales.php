<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class Preoperacionales extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = 'preoperacionales';

    protected $primaryKey = 'id';
}
