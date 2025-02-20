<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;
    
    protected $table = "_departamentos";
    protected $primarykey = "IdDepartamento";
    public $timestamps = false;

    protected $fillable = [
        "IdDepartamento","DepNom","DepNomMin"
    ];
}
