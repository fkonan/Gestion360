<?php

namespace App\Providers;

use App\View\Composers\MenuComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\GESTIONADMIN\Modulo;
use App\Models\GESTIONADMIN\Permisos;
use App\Models\GESTIONADMIN\SubModulo;
use App\Observers\ModuloObserver;
use App\Observers\PermisoObserver;
use App\Observers\SubmoduloObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void{
        // Carga el menú de navegación en todas las vistas
        View::composer('menu', MenuComposer::class);

        //Carga nuevamente los modulos y submodulos en caché cuando se crean, actualizan o cambian su estado
        Modulo::observe(ModuloObserver::class);
        SubModulo::observe(SubmoduloObserver::class);
        Permisos::observe(PermisoObserver::class);


        //Directiva para verificar si un usuario tiene un permiso específico
        Blade::if('permite', function ($permiso) {
            return Auth::check()
                && !empty($permiso)
                && permisoExiste($permiso)
                && Auth::user()->can($permiso);
        });
    }
}
