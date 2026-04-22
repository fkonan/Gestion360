<?php

use App\Models\GESTIONADMIN\AutogestionNotificacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::get('/', function () {
    return view('index');
})->name('index');

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
