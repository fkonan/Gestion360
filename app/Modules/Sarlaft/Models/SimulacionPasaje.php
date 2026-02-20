<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulacionPasaje extends Model
{
    protected $table = 'sarlaft_simulacion_pasajes';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'consulta_id',
        'usuario_id',
        'ciudad_origen_id',
        'ciudad_origen_nombre',
        'ciudad_destino_id',
        'ciudad_destino_nombre',
        'fecha_viaje',
        'tipo_documento',
        'documento',
        'nombres',
        'apellidos',
        'direccion',
        'telefono',
        'correo',
        'encontrado',
        'presta_servicio',
        'nivel_riesgo',
        'coincidencias',
    ];

    protected function casts(): array
    {
        return [
            'fecha_viaje' => 'date',
            'encontrado' => 'boolean',
            'presta_servicio' => 'boolean',
            'coincidencias' => 'array',
        ];
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }
}
