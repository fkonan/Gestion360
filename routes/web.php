<?php

use App\Constants\Roles;
use App\Models\GESTIONADMIN\AutogestionNotificacion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/clear', function () {
    Artisan::call('storage:link');
    Artisan::call('cache:clear');
    /* Artisan::call('config:cache') */
    Artisan::call('view:clear');

    /* Artisan::call('route:cache'); */
    return 'Cleared!';
})->middleware(['auth', 'can:'.Roles::SUPER_ADMIN])->name('clear');

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
require __DIR__.'/auth.php';
