<?php

namespace App\Modules\Administration\Services;

use App\Models\GESTIONADMIN\Modulo;

class ModuloService
{
  public function modulosActivosConSubmodulos()
  {
    /* Carga los modulos con los submodulos,
        1.Se verifica que los submodulos tenga alguna ruta establecida de lo contrario no se mostrara el submodulo.
        2.Si en un modulo ningun submodulo tiene rutas entonces no se mostrata ese modulo.      */

    $modulos = Modulo::with(['submodulos' => function ($query) {
      $query->where('SubModuloEstado', 'ACTIVO')
        ->whereNotNull('SubModRuta');
    }])
      ->where('ModuloEstado', 'ACTIVO')
      ->get()
      ->filter(function ($modulo) {
        return $modulo->submodulos->isNotEmpty();
      })
      ->values();

    return $modulos;
  }
}
