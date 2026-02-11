<?php

namespace App\Modules\RadFact\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RadFactProveedor extends Model
{
    protected $table = 'rad_fact_proveedores';

    protected $fillable = [
        'tipo_documento',
        'documento',
        'nombres',
        'apellidos',
        'razon_social',
        'telefono',
        'correo',
        'user_id',
    ];

    /**
     * Usuario que registró el proveedor
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
    }

    /**
     * Radicaciones del proveedor
     */
    public function radicaciones(): HasMany
    {
        return $this->hasMany(RadFactRadicacion::class, 'proveedor_id');
    }

    /**
     * Nombre completo o razón social
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->razon_social) {
            return $this->razon_social;
        }

        return trim("{$this->nombres} {$this->apellidos}");
    }
}
