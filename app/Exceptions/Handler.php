<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
  /**
   * Lista de las excepciones que no se reportan.
   *
   * @var array<int, class-string<\Throwable>>
   */
  protected $dontReport = [];

  /**
   * Lista de las entradas que no se validan.
   *
   * @var array<int, string>
   */
  protected $dontFlash = [
    'current_password',
    'password',
    'password_confirmation',
  ];

  /**
   * Reporta o loguea una excepción.
   */
  public function report(Throwable $exception): void
  {
    parent::report($exception);
  }

  /**
   * Renderiza una excepción en una respuesta HTTP.
   */
  public function render($request, Throwable $exception)
  {
    if ($exception instanceof MethodNotAllowedHttpException) {
      return response()->view('errors.405', [], 405);
    }

    if ($exception instanceof NotFoundHttpException) {
      return response()->view('errors.404');
    }

    if ($exception instanceof UnauthorizedException) {
      session()->flash('alert', ['type' => 'warning', 'title' => 'No tienes los permisos necesarios para acceder.']);
      return redirect()->back();
    }

    if ($exception instanceof TokenMismatchException) {
      return toast('Tu sesión ha expirado. Por favor, inicia sesión de nuevo.', 'danger', route('login.form'));
    }

    return parent::render($request, $exception);
  }
}
