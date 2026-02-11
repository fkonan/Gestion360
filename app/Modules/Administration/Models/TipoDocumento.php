<?php

namespace App\Modules\Administration\Models;

use App\Models\GESTIONADMIN\Persona;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $connection = 'mysql-gestion-admin';

    protected $table = '_tipo_documento';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nomenclatura',
        'nombre',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'PerTipoDoc', 'id');
    }
}
