<?php

namespace App\View\Composers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class MenuComposer
{
  public function compose(View $view): void
  {

    $modulos = Cache::remember('modulos_con_submodulos', now()->addHours(1), function () {

      // Aquí se puede definir el orden de los módulos según su ID
      $ordenPersonalizado = [
        11 => 999,  // Configuración (último)
      ];

      return Modulo::with(['submodulos' => function ($query) {
        $query->where('SubModuloEstado', 'ACTIVO')
          ->whereNotNull('SubModRuta');
      }])
        ->where('ModuloEstado', 'ACTIVO')
        ->get()
        // Filtrar módulos que tienen submódulos activos
        ->filter(function ($modulo) {
          return $modulo->submodulos->isNotEmpty();
        })
        // Ordenar módulos por ID, aplicando el orden personalizado
        ->sortBy(function ($modulo) use ($ordenPersonalizado) {
          return $ordenPersonalizado[$modulo->IdModulo] ?? (1 . strtolower($modulo->ModNom));
        })
        ->values();
    });

    $view->with('modulos', $modulos);
  }
}
