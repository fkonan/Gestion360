<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;

class FirmaPreingreso extends Model
{
    protected $connection = 'mysql-gestion-admin';
    protected $table = "firmas_normas_preingreso";
    protected $primaryKey = "Id";
}
