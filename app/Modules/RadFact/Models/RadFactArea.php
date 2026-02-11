<?php

namespace App\Modules\RadFact\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadFactArea extends Model
{
    protected $table = 'rad_fact_areas';

    protected $fillable = [
        'user_id',
        'area',
        'responsable',
        'correo',
        'subgerencia',
        'compras',
    ];

    protected function casts(): array
    {
        return [
            'subgerencia' => 'boolean',
            'compras' => 'boolean',
        ];
    }

    /**
     * Usuario responsable del área
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
    }

    /**
     * Distribuciones asignadas a esta área
     */
    public function distribuciones(): HasMany
    {
        return $this->hasMany(RadFactDistribucion::class, 'area_id');
    }

    /**
     * Distribuciones activas asignadas a esta área
     */
    public function distribucionesActivas(): HasMany
    {
        return $this->hasMany(RadFactDistribucion::class, 'area_id')
            ->where('activo', true);
    }

    /**
     * Scope: Área de subgerencia
     */
    public function scopeSubgerencia($query)
    {
        return $query->where('subgerencia', true);
    }

    /**
     * Scope: Área de compras
     */
    public function scopeCompras($query)
    {
        return $query->where('compras', true);
    }
}
