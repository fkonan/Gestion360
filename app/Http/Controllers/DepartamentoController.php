<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Departamento;

class DepartamentoController extends Controller
{
    public function getMunici($idDepar){
        $municipios = Departamento::findOrFail($idDepar)->municipios;
        return response()->json($municipios);
    }
}
