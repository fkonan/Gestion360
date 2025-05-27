<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartamentoPasajes extends Model
{
    use HasFactory;
    
    protected $connection = 'mysql-gestion-pasajes';
    protected $table = "_departamentos";
    protected $primaryKey = "IdDepartamento";
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        "IdDepartamento","DepNom","Abreviacion"
    ];

    public function municipios(): HasMany{
        return $this->hasMany(MunicipioPasajes::class,'DepartamentoId','IdDepartamento');
    }
}
