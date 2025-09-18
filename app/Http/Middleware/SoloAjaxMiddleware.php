<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloAjaxMiddleware
{
  //Las rutas que se cargan en modales solo podran ser accedidas mediante peticiones AJAX
  public function handle(Request $request, Closure $next): Response
  {
    if (!$request->ajax() && !$request->expectsJson()) {
      abort(403, 'Acceso denegado');
    }
    return $next($request);
  }
}
