<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class Arl extends Model
{
    protected $connection = 'mysql-gestion-humana';

    protected $table = 'arl';

    protected $primaryKey = 'IdARL';

    public $timestamps = false;

    public function incapacidadesAsociadas()
    {
        return $this->hasMany(Incapacidad::class, 'ARLId', 'IdARL');
    }
}
