<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DesbloqueoFicsPendiente extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PROCESANDO = 'procesando';
    public const ESTADO_FALLIDO = 'fallido';
    public const ESTADO_RESUELTO = 'resuelto';

    protected $connection = 'mysql-gestion-pasajes';

    protected $table = 'bloqueos_pendientes';

    protected $fillable = [
        'identificacion',
        'id_bloqueo_fics',
        'reactivar_si_sin_bloqueos',
        'origen',
        'estado',
        'intentos',
        'ultimo_error',
        'ultimo_intento_at',
        'proximo_intento_at',
        'resuelto_at',
    ];

    protected $casts = [
        'reactivar_si_sin_bloqueos' => 'boolean',
        'intentos' => 'integer',
        'ultimo_intento_at' => 'datetime',
        'proximo_intento_at' => 'datetime',
        'resuelto_at' => 'datetime',
    ];

    public function scopeAbiertos(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            self::ESTADO_PENDIENTE,
            self::ESTADO_FALLIDO,
        ])->whereNull('resuelto_at');
    }
}
