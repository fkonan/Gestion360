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
        'sistema_origen',
        'modo_integracion',
        'tipo_documento',
        'numero_documento',
        'nombre',
        'tipo_lista',
        'lista_nombre',
        'tipo_operacion',
        'referencia',
        'referencia_externa',
        'fecha_operacion',
        'origen',
        'destino',
        'monto',
        'moneda',
        'descripcion',
        'contexto',
        'ip_origen',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_operacion' => 'datetime',
            'created_at' => 'datetime',
            'monto' => 'decimal:2',
            'contexto' => 'array',
        ];
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(SistemaConsumidor::class, 'sistema_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'intento_id');
    }
}
