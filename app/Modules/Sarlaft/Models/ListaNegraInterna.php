<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListaNegraInterna extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_lista_negra_interna';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'tipo_entidad',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'motivo',
        'creado_por',
        'estado',
    ];

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'IdUsuario');
    }
}
