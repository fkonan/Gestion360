<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consulta extends Model
{
    public $timestamps = false;

    protected $table = 'sarlaft_consultas';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'sistema_origen',
        'tipo_documento',
        'numero_documento',
        'nombre_consultado',
        'encontrado',
        'presta_servicio',
        'nivel_riesgo',
        'coincidencias',
        'contexto_operacion',
        'ip_origen',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'encontrado' => 'boolean',
            'presta_servicio' => 'boolean',
            'coincidencias' => 'array',
            'contexto_operacion' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'consulta_id');
    }

    public function simulacionPasaje(): HasOne
    {
        return $this->hasOne(SimulacionPasaje::class, 'consulta_id');
    }

    public function simulacionRemesa(): HasOne
    {
        return $this->hasOne(SimulacionRemesa::class, 'consulta_id');
    }
}
