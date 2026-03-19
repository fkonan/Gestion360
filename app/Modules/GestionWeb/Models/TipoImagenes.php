<?php

namespace App\Modules\GestionWeb\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoImagenes extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = '_tipo_recursos_digitales';

    protected $primaryKey = 'IdTipoRecurso';

    protected $fillable = [
        'Descripcion',
    ];

    protected $casts = [
        'IdTipoRecurso' => 'integer',
    ];

    public $timestamps = false;

    public function recursosDigitales(): HasMany
    {
        return $this->hasMany(RecursosDigitales::class, 'Tipo', 'IdTipoRecurso');
    }
}
