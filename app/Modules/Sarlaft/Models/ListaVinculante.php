<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListaVinculante extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_listas_vinculantes';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'nombre',
        'tipo',
        'url_fuente',
        'frecuencia_sync',
        'activa',
        'ultima_sincronizacion',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'ultima_sincronizacion' => 'datetime',
        ];
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroLista::class, 'lista_id');
    }

    public function sincronizacionLogs(): HasMany
    {
        return $this->hasMany(SincronizacionLog::class, 'lista_id');
    }
}
