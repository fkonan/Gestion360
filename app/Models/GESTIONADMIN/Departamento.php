<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    use HasFactory;
    
    protected $connection = 'mysql-gestion-admin';
    protected $table = "_departamentos";
    protected $primaryKey = "IdDepartamento";
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        "IdDepartamento","DepNom","DepNomMin"
    ];

    public function municipios(): HasMany{
        return $this->hasMany(Municipio::class,'IdDepartamento','IdDepartamento');
    }
}
