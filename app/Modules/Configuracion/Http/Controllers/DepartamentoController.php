<?php

namespace App\Modules\Configuracion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Configuracion\Models\Departamento;

class DepartamentoController extends Controller
{
    public function getMunici($idDepar)
    {
        $municipios = Departamento::findOrFail($idDepar)->municipios;

        return response()->json($municipios);
    }
}
