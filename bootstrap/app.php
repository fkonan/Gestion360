<?php

use App\Http\Middleware\ModuloActivoMiddleware;
use App\Http\Middleware\SoloAjaxMiddleware;
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
            'soloAJAX' => SoloAjaxMiddleware::class, 
            'modulo.activo' => ModuloActivoMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
