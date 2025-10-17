<?php

use App\Http\Middleware\CajaActivaMiddleware;
use App\Http\Middleware\ModuloActivoMiddleware;
use App\Http\Middleware\SoloAjaxMiddleware;
use App\Http\Middleware\SubModuloActivoMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permisos' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'caja.activa' => CajaActivaMiddleware::class,
            'soloAJAX' => SoloAjaxMiddleware::class,
            'modulo.activo' => ModuloActivoMiddleware::class,
            'submodulo.activo' => SubModuloActivoMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
