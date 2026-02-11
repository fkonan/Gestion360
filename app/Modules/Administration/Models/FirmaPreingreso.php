<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class FirmaPreingreso extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = 'firmas_normas_preingreso';

    protected $primaryKey = 'Id';
}
