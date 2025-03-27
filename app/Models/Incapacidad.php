<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incapacidad extends Model
{
    protected $connection = 'mysql-gestion-humana';

    protected $table = "incapacidades";
    protected $primaryKey = "IdIncapacidad";

    public $timestamps = false;
}
