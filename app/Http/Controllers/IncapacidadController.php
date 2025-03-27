<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use Illuminate\Http\Request;

class IncapacidadController extends Controller
{
    public function index(){     
        $incapacidades = Incapacidad::all();
        return view("incapacidades.indexIncapacidades",compact("incapacidades"));
    }
}
