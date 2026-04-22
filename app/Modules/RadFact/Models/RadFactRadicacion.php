<?php

namespace App\Modules\RadFact\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadFactRadicacion extends Model
{
    protected $table = 'rad_fact_radicaciones';

    protected $fillable = [
        'user_id',
        'proveedor_id',
        'num_factura',
        'num_contrato',
        'numero_pagos',
        'fecha_radicacion',
        'fecha_vencimiento',
        'necesita_visto_bueno',
        'descripcion',
        'valor',
        'pdf',
        'observacion',
        'estado',
        // Subgerencia
        'fecha_envio_subgerencia',
        'estado_subgerencia',
        'observacion_subgerencia',
        'usuario_subgerencia_id',
        'fecha_respuesta_subgerencia',
        // Compras
        'fecha_envio_compras',
        'estado_compras',
        'observacion_compras',
        'usuario_compras_id',
        'fecha_respuesta_compras',
        // Pago
        'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'fecha_radicacion' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_envio_subgerencia' => 'date',
            'fecha_envio_compras' => 'date',
            'fecha_pago' => 'date',
            'fecha_respuesta_subgerencia' => 'datetime',
            'fecha_respuesta_compras' => 'datetime',
            'necesita_visto_bueno' => 'boolean',
            'valor' => 'decimal:2',
            'numero_pagos' => 'integer',
        ];
    }

    // ==================== RELACIONES ====================

    /**
     * Usuario que radica
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
    }

    /**
     * Proveedor de la factura
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(RadFactProveedor::class, 'proveedor_id');
    }

    /**
     * Todas las distribuciones (incluye historial)
     */
    public function distribuciones(): HasMany
    {
        return $this->hasMany(RadFactDistribucion::class, 'radicacion_id');
    }

    /**
     * Distribuciones activas (vigentes)
     */
    public function distribucionesActivas(): HasMany
    {
        return $this->hasMany(RadFactDistribucion::class, 'radicacion_id')
            ->where('activo', true);
    }

    /**
     * Distribuciones inactivas (historial)
     */
    public function distribucionesHistorial(): HasMany
    {
        return $this->hasMany(RadFactDistribucion::class, 'radicacion_id')
            ->where('activo', false);
    }

    /**
     * Usuario que aprobó/rechazó en subgerencia
     */
    public function usuarioSubgerencia(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_subgerencia_id', 'IdUsuario');
    }

    /**
     * Usuario que aprobó/rechazó en compras
     */
    public function usuarioCompras(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_compras_id', 'IdUsuario');
    }

    // ==================== SCOPES ====================

    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeRadicado($query)
    {
        return $query->where('estado', 'RADICADO');
    }

    public function scopeEnAprobacion($query)
    {
        return $query->where('estado', 'EN_APROBACION');
    }

    public function scopePendienteSubgerencia($query)
    {
        return $query->where('estado', 'PENDIENTE_SUBGERENCIA');
    }

    public function scopePendienteCompras($query)
    {
        return $query->where('estado', 'PENDIENTE_COMPRAS');
    }

    public function scopeAprobado($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    public function scopeRechazado($query)
    {
        return $query->where('estado', 'RECHAZADO');
    }

    public function scopePagado($query)
    {
        return $query->where('estado', 'PAGADO');
    }

    // ==================== HELPERS ====================

    /**
     * Verifica si todas las distribuciones activas están aprobadas
     */
    public function todasDistribucionesAprobadas(): bool
    {
        $activas = $this->distribucionesActivas()->with('aprobacion')->get();

        if ($activas->isEmpty()) {
            return false;
        }

        return $activas->every(function ($distribucion) {
            return $distribucion->aprobacion && $distribucion->aprobacion->estado === 'APROBADO';
        });
    }

    /**
     * Verifica si alguna distribución activa fue rechazada
     */
    public function algunaDistribucionRechazada(): bool
    {
        return $this->distribucionesActivas()
            ->whereHas('aprobacion', function ($query) {
                $query->where('estado', 'RECHAZADO');
            })
            ->exists();
    }

    /**
     * Obtiene las distribuciones rechazadas con sus observaciones
     */
    public function distribucionesRechazadas()
    {
        return $this->distribucionesActivas()
            ->whereHas('aprobacion', function ($query) {
                $query->where('estado', 'RECHAZADO');
            })
            ->with(['aprobacion', 'area'])
            ->get();
    }
}
