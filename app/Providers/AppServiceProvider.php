<?php

namespace App\Providers;

use App\Modules\Configuracion\Models\Modulo;
use App\Modules\Configuracion\Models\Permisos;
use App\Modules\Configuracion\Models\SubModulo;
use App\Modules\Configuracion\Observers\ModuloObserver;
use App\Modules\Configuracion\Observers\SubmoduloObserver;
use App\Observers\PermisoObserver;
use App\View\Composers\MenuComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        // Carga el menu de navegacion en todas las vistas
        View::composer('menu', MenuComposer::class);

        // Carga nuevamente los modulos y submodulos en cache cuando se crean, actualizan o cambian su estado
        Modulo::observe(ModuloObserver::class);
        SubModulo::observe(SubmoduloObserver::class);
        Permisos::observe(PermisoObserver::class);

        // Directiva para verificar si un usuario tiene un permiso especifico
        Blade::if('permite', function ($permiso) {
            return Auth::check()
              && ! empty($permiso)
              && permisoExiste($permiso)
              && Auth::user()->can($permiso);
        });

        // Rate limiter de emision de token SARLAFT: por client_id (no solo IP),
        // para frenar fuerza bruta del secret aunque se distribuya entre IPs.
        RateLimiter::for('sarlaft-token', function (Request $request) {
            $clientId = (string) $request->input('client_id', '');
            $key = $clientId !== '' ? 'cid:'.$clientId : 'ip:'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(20)->by('ip:'.$request->ip()),
            ];
        });

        // Rate limiter de consulta de listas SARLAFT: por sistema (claim del JWT),
        // para limitar enumeracion masiva de documentos. Los topes son generosos
        // porque un unico cliente (empresa) consulta por muchas sucursales; se
        // pueden ajustar por .env sin tocar codigo.
        RateLimiter::for('sarlaft-consulta', function (Request $request) {
            $claims = $request->attributes->get('api_jwt_claims');
            $sistemaId = is_array($claims) ? ($claims['sistema_id'] ?? null) : null;
            // Prefijo de modulo en la key para no colisionar con otros throttles por IP.
            $key = $sistemaId !== null ? 'sarlaft-sis:'.$sistemaId : 'sarlaft-ip:'.$request->ip();

            $porMinuto = (int) config('sarlaft.consulta_rate_minuto', 300);
            $porDia = (int) config('sarlaft.consulta_rate_dia', 30000);

            return [
                Limit::perMinute($porMinuto)->by($key),
                Limit::perDay($porDia)->by($key),
            ];
        });
    }
}
