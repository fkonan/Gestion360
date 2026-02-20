<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulacionRemesa extends Model
{
    protected $table = 'sarlaft_simulacion_remesas';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'consulta_id',
        'usuario_id',
        'ciudad_origen_id',
        'ciudad_origen_nombre',
        'ciudad_destino_id',
        'ciudad_destino_nombre',
        'fecha_envio',
        'tipo_documento',
        'documento_remitente',
        'nombres_remitente',
        'apellidos_remitente',
        'telefono_remitente',
        'nombre_destinatario',
        'documento_destinatario',
        'monto',
        'concepto',
        'encontrado',
        'presta_servicio',
        'nivel_riesgo',
        'coincidencias',
    ];

    protected function casts(): array
    {
        return [
            'fecha_envio' => 'date',
            'monto' => 'decimal:2',
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
