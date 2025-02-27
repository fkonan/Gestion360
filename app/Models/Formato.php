<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Formato extends Model
{
    use HasFactory;

    protected $table = "_formatos";
    protected $primaryKey = "IdFormato";
    public $timestamps = false;

    protected $fillable = [
        "IdFormato","FormCod","FormNom","FormTipo","FormUbicacion"
    ];

    public function versiones(){
        return $this->hasMany(FormatoVersion::class,'IdFormato','IdFormato');
    }

    public function ultimaVersion(){
        return $this->hasOne(FormatoVersion::class,'IdFormato','IdFormato')->orderBy('Version','desc');
    }
}
