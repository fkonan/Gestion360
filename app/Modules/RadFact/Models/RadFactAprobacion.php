<?php

namespace App\Modules\RadFact\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadFactAprobacion extends Model
{
    protected $table = 'rad_fact_aprobaciones';

    protected $fillable = [
        'user_id',
        'distribucion_id',
        'estado',
        'observacion',
        'fecha_respuesta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_respuesta' => 'datetime',
        ];
    }

    // ==================== RELACIONES ====================

    /**
     * Usuario que aprueba/rechaza
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
    }

    /**
     * Distribución que se aprueba/rechaza
     */
    public function distribucion(): BelongsTo
    {
        return $this->belongsTo(RadFactDistribucion::class, 'distribucion_id');
    }

    // ==================== SCOPES ====================

    public function scopePendiente($query)
    {
        return $query->where('estado', 'PENDIENTE');
    }

    public function scopeAprobado($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    public function scopeRechazado($query)
    {
        return $query->where('estado', 'RECHAZADO');
    }

    // ==================== HELPERS ====================

    /**
     * Aprueba la distribución
     */
    public function aprobar(int $userId, ?string $observacion = null): bool
    {
        return $this->update([
            'user_id' => $userId,
            'estado' => 'APROBADO',
            'observacion' => $observacion,
            'fecha_respuesta' => now(),
        ]);
    }

    /**
     * Rechaza la distribución
     */
    public function rechazar(int $userId, string $observacion): bool
    {
        return $this->update([
            'user_id' => $userId,
            'estado' => 'RECHAZADO',
            'observacion' => $observacion,
            'fecha_respuesta' => now(),
        ]);
    }

    /**
     * Acceso rápido al área a través de la distribución
     */
    public function getAreaAttribute()
    {
        return $this->distribucion?->area;
    }

    /**
     * Acceso rápido a la radicación a través de la distribución
     */
    public function getRadicacionAttribute()
    {
        return $this->distribucion?->radicacion;
    }
}
