<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigPoliticas extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = 'config_politicas';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
