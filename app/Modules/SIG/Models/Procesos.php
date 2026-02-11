<?php

namespace App\Modules\SIG\Models;

use Illuminate\Database\Eloquent\Model;

class Procesos extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = 'sig_procesos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function documentos()
    {
        return $this->hasMany(Documentos::class, 'id_proceso');
    }
}
