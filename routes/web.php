<?php

use App\Constants\Permisos;
use App\Constants\Roles;
use App\Modules\Administration\Http\Controllers\GestionPasajesController;
use App\Modules\GestionWeb\Http\Controllers\TrackingRemesasController;
use App\Models\GESTIONADMIN\AutogestionNotificacion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/home', function () {
  return view('home');
})->middleware('auth')->name('home');

Route::get('/', function () {
  return view('index');
})->name('index');

Route::get('/clear', function () {
  Artisan::call('storage:link');
  Artisan::call('cache:clear');
    /* Artisan::call('config:cache') */;
  Artisan::call('view:clear');
  /* Artisan::call('route:cache'); */
  return "Cleared!";
})->middleware(['auth', 'can:' . Roles::SUPER_ADMIN])->name('clear');


//Rutas Modulo Gestion Pasajes
Route::prefix("gestionPasajes")->middleware(['auth', 'permisos:' . Permisos::GESTION_PASAJES_ACCEDER, 'modulo.activo:13'])->group(function () {
  Route::prefix("buscar-viaje")->middleware('submodulo.activo:23')->group(function () {
    Route::get("/", [GestionPasajesController::class, "formBuscarViaje"])->name("buscar-viaje.index");
    Route::post("/filtrar", [GestionPasajesController::class, "filtrarViajes"])->name("buscar-viaje.filtrar");
  });
});

//Tracking remesas
Route::get('/tracking-remesas', function () {
  return view('remesas.trackingRemesas');
})->name('trackingRemesas.index');

Route::post('/tracking-remesas/consultar', [TrackingRemesasController::class, 'consultar'])->name('trackingRemesas.consultar');


//Impresion tiquietes gestion pasajes
Route::get('/imprimir-tiquete/{id}', [GestionPasajesController::class, 'imprimirTiquetes'])->name('imprimir-tiquetes');


// Notificaciones (placeholder para pruebas de campana)
Route::middleware('auth')->group(function () {
  Route::get('/notificaciones', function () {
    return AutogestionNotificacion::where('user_id', Auth::id())
      ->whereNull('leida_en')
      ->orderByDesc('id')
      ->limit(20)
      ->get();
  })->name('notificaciones.sig.index');

  Route::post('/notificaciones/{id}/leer', function ($id) {
    AutogestionNotificacion::where('id', $id)
      ->where('user_id', Auth::id())
      ->update(['leida_en' => now()]);
    return ['ok' => true];
  })->name('notificaciones.sig.leer');
});

// Rutas publicas
require __DIR__ . '/auth.php';

// Rutas por módulos
require __DIR__ . '/modulos/administracion.php';
require __DIR__ . '/modulos/configuracion.php';
require __DIR__ . '/modulos/gestion-rrhh.php';
require __DIR__ . '/modulos/gestionWeb.php';
require __DIR__ . '/modulos/pagosRecaudos.php';
require __DIR__ . '/modulos/sig.php';
