<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use Illuminate\Http\Request;

class IncapacidadController extends Controller
{
    public function index(){     
        return view("incapacidades.indexIncapacidades");
    }

    public function listaIncapacidades(){
        $incapacidades = Incapacidad::with("causaIncapacidad")->get();
        return view("incapacidades.listaIncapacidades",compact("incapacidades"));
    }

    public function incapacidadesSeguimiento(){
        $incapacidadesSeguimiento = Incapacidad::all();
        return view("incapacidades.seguimiento",compact("incapacidadesSeguimiento"));
    }
}
