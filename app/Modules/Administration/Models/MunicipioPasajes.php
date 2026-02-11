<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipioPasajes extends Model
{
    use HasFactory;

    protected $connection = 'mysql-gestion-pasajes';

    protected $table = '_municipios';

    protected $primaryKey = 'IdMunicipio';

    public $incrementing = false;

    public $timestamps = false;

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(DepartamentoPasajes::class, 'DepartamentoId', 'IdDepartamento');
    }
}
