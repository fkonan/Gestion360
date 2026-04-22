<?php

namespace App\Modules\RadFact\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RadFactDistribucion extends Model
{
    protected $table = 'rad_fact_distribuciones';

    protected $fillable = [
        'user_id',
        'radicacion_id',
        'area_id',
        'porcentaje',
        'valor_calculado',
        'observacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'valor_calculado' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    // ==================== RELACIONES ====================

    /**
     * Usuario que creó la distribución
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
    }

    /**
     * Radicación a la que pertenece
     */
    public function radicacion(): BelongsTo
    {
        return $this->belongsTo(RadFactRadicacion::class, 'radicacion_id');
    }

    /**
     * Área asignada
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(RadFactArea::class, 'area_id');
    }

    /**
     * Aprobación de esta distribución (relación 1:1)
     */
    public function aprobacion(): HasOne
    {
        return $this->hasOne(RadFactAprobacion::class, 'distribucion_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeInactivas($query)
    {
        return $query->where('activo', false);
    }

    public function scopePendientes($query)
    {
        return $query->where('activo', true)
            ->whereHas('aprobacion', function ($q) {
                $q->where('estado', 'PENDIENTE');
            });
    }

    public function scopeAprobadas($query)
    {
        return $query->where('activo', true)
            ->whereHas('aprobacion', function ($q) {
                $q->where('estado', 'APROBADO');
            });
    }

    public function scopeRechazadas($query)
    {
        return $query->where('activo', true)
            ->whereHas('aprobacion', function ($q) {
                $q->where('estado', 'RECHAZADO');
            });
    }

    // ==================== HELPERS ====================

    /**
     * Verifica si está aprobada
     */
    public function estaAprobada(): bool
    {
        return $this->aprobacion && $this->aprobacion->estado === 'APROBADO';
    }

    /**
     * Verifica si está rechazada
     */
    public function estaRechazada(): bool
    {
        return $this->aprobacion && $this->aprobacion->estado === 'RECHAZADO';
    }

    /**
     * Verifica si está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->aprobacion && $this->aprobacion->estado === 'PENDIENTE';
    }

    /**
     * Desactiva esta distribución (para crear una nueva versión)
     */
    public function desactivar(): bool
    {
        return $this->update(['activo' => false]);
    }
}
