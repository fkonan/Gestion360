<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaDatos extends Model
{
    use HasFactory;

    protected $table ="_personas_datos";
    protected $primaryKey ="IdPersonaDatos";
    public $timestamps = false;

    public function persona(){
        return $this->belongsTo(Persona::class,"IdPersona","IdPersona");
    }
}
