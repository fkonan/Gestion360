<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = "_tipo_documento";
    protected $primaryKey = "id";
        
    public $timestamps = false;

    protected $fillable = [
        "nomenclatura","nombre"
    ];

    public function personas(): HasMany{
        return $this->hasMany(Persona::class,"PerTipDoc","id");
    }
}
