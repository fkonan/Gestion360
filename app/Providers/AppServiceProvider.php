<?php

namespace App\Providers;

use App\Modules\Configuracion\Models\Modulo;
use App\Modules\Configuracion\Models\Permisos;
use App\Modules\Configuracion\Models\SubModulo;
use App\Modules\Configuracion\Observers\ModuloObserver;
use App\Modules\Configuracion\Observers\SubmoduloObserver;
use App\Modules\Sarlaft\Events\AlertaGenerada;
use App\Modules\Sarlaft\Events\BloqueoCreado;
use App\Modules\Sarlaft\Events\ConsultaRealizada;
use App\Modules\Sarlaft\Listeners\NotificarAlerta;
use App\Modules\Sarlaft\Listeners\RegistrarBloqueo;
use App\Modules\Sarlaft\Listeners\RegistrarConsulta;
use App\Observers\PermisoObserver;
use App\View\Composers\MenuComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Carga el menú de navegación en todas las vistas
        View::composer('menu', MenuComposer::class);

        // Carga nuevamente los modulos y submodulos en caché cuando se crean, actualizan o cambian su estado
        Modulo::observe(ModuloObserver::class);
        SubModulo::observe(SubmoduloObserver::class);
        Permisos::observe(PermisoObserver::class);

        // Directiva para verificar si un usuario tiene un permiso específico
        Blade::if('permite', function ($permiso) {
            return Auth::check()
              && ! empty($permiso)
              && permisoExiste($permiso)
              && Auth::user()->can($permiso);
        });

        Event::listen(ConsultaRealizada::class, RegistrarConsulta::class);
        Event::listen(AlertaGenerada::class, NotificarAlerta::class);
        Event::listen(BloqueoCreado::class, RegistrarBloqueo::class);
    }
}
