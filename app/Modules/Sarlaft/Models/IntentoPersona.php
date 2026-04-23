<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentoPersona extends Model
{
    protected $table = 'sarlaft_intento_personas';

    protected $connection = 'mysql-sarlaft';

    public $timestamps = false;

    protected $fillable = [
        'intento_id',
        'tipo_documento',
        'numero_documento',
        'nombre',
        'rol',
        'tipo_lista',
        'lista_nombre',
        'detalle_coincidencia',
    ];

    protected function casts(): array
    {
        return [
            'detalle_coincidencia' => 'array',
        ];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoOperacion::class, 'intento_id');
    }
}
