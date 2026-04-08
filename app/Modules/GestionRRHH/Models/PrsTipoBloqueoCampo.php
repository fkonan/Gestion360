<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PrsTipoBloqueoCampo extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = 'prs_tipo_bloqueo_campos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'tipo_bloqueo_id',
        'nombre_campo',
        'label',
        'tipo_input',
        'requerido',
        'orden',
        'placeholder',
        'help_text',
        'reglas_laravel',
        'valor_default',
        'fuente_opciones',
        'opciones_json',
        'visible',
        'estado',
    ];

    protected $casts = [
        'requerido' => 'boolean',
        'visible' => 'boolean',
        'estado' => 'boolean',
        'opciones_json' => 'array',
    ];

    public function tipoBloqueo()
    {
        return $this->belongsTo(PrsTipoBloqueo::class, 'tipo_bloqueo_id', 'id');
    }
}
