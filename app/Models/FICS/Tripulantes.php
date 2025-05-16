<?php

namespace App\Models\FICS;

use Illuminate\Database\Eloquent\Model;

class Tripulantes extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "Tripulantes";
    protected $primaryKey = "Id";
    public $incrementing = false;
    public $timestamps = false;
}
