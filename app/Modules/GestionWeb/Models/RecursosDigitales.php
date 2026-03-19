<?php

namespace App\Modules\GestionWeb\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecursosDigitales extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = '_recursos_digitales';

    protected $primaryKey = 'IdRecurso';

    protected $fillable = [
        'TextoAuxiliar',
        'URL',
        'Tipo',
        'Orden',
        'Estado',
    ];

    protected $casts = [
        'IdRecurso' => 'integer',
        'Tipo' => 'integer',
        'Orden' => 'integer',
    ];

    public $timestamps = false;

    public function tipoImagen(): BelongsTo
    {
        return $this->belongsTo(TipoImagenes::class, 'Tipo', 'IdTipoRecurso');
    }
}
