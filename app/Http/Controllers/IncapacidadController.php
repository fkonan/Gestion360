<?php

namespace App\Http\Controllers;

use App\Models\Incapacidad;
use Illuminate\Http\Request;

class IncapacidadController extends Controller
{
    public function listaIncapacidades(){
        return view("incapacidades.listaIncapacidades");
    }

    public function cargarDatos(){
        $incapacidades = Incapacidad::with(['causa', 'diagnostico', 'eps', 'arl'])->get();
        return $incapacidades;
    }

    public function incapacidadesSeguimiento(){
        $incapacidadesSeguimiento = Incapacidad::all();
        return view("incapacidades.seguimiento",compact("incapacidadesSeguimiento"));
    }
}
