<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntentoOperacion extends Model
{
    protected $table = 'sarlaft_intentos_operacion';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'sistema_id',
        'modo_integracion',
        'tipo_operacion',
        'referencia_externa',
        'fecha_operacion',
        'origen',
        'destino',
        'monto',
        'moneda',
        'descripcion',
        'contexto',
        'ip_origen',
    ];

    protected function casts(): array
    {
        return [
            'fecha_operacion' => 'datetime',
            'monto' => 'decimal:2',
            'contexto' => 'array',
        ];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(SistemaConsumidor::class, 'sistema_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(IntentoPersona::class, 'intento_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'intento_id');
    }
}
