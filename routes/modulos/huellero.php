<?php

use App\Http\Controllers\Huellero\DashboardController;
use App\Http\Controllers\Huellero\CapturaController;
use App\Http\Controllers\Huellero\VerificacionController;
use App\Http\Controllers\Huellero\EventosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo Huellero
|--------------------------------------------------------------------------
|
| Estas rutas manejan la funcionalidad del sistema de huellas dactilares
| incluyendo captura, verificación, eventos y dashboard.
|
*/

// Grupo de rutas con middleware de autenticación
Route::middleware(['auth'])->prefix('huellero')->name('huellero.')->group(function () {

    // Dashboard principal
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/datos', [DashboardController::class, 'datosRealTime'])->name('dashboard.datos');
    Route::get('/dashboard/dispositivos', [DashboardController::class, 'dispositivos'])->name('dashboard.dispositivos');
    Route::post('/dashboard/exportar', [DashboardController::class, 'exportar'])->name('dashboard.exportar');

    // Demo de integración
    Route::get('/demo', function () {
        return view('huellero.demo.index');
    })->name('demo.index');

    // Captura de huellas
    Route::prefix('captura')->name('captura.')->group(function () {
        Route::get('/', [CapturaController::class, 'index'])->name('index');
        Route::post('/iniciar', [CapturaController::class, 'iniciarCaptura'])->name('iniciar');
        Route::post('/detener', [CapturaController::class, 'detenerCaptura'])->name('detener');
        Route::post('/procesar', [CapturaController::class, 'procesarCaptura'])->name('procesar');
        Route::post('/registrar', [CapturaController::class, 'registrarHuella'])->name('registrar');
        Route::post('/verificar-calidad', [CapturaController::class, 'verificarCalidad'])->name('verificar_calidad');
        Route::get('/huellas-persona', [CapturaController::class, 'huellasPersona'])->name('huellas_persona');
    });

    // Verificación de huellas
    Route::prefix('verificacion')->name('verificacion.')->group(function () {
        Route::get('/', [VerificacionController::class, 'index'])->name('index');
        Route::post('/verificar-huella', [VerificacionController::class, 'verificarHuella'])->name('verificar_huella');
        Route::post('/buscar-persona', [VerificacionController::class, 'buscarPersona'])->name('buscar_persona');
        Route::get('/estadisticas-hoy', [VerificacionController::class, 'estadisticasHoy'])->name('estadisticas_hoy');
        Route::get('/estado-sistema', [VerificacionController::class, 'estadoSistema'])->name('estado_sistema');
    });

    // Eventos y reportes
    Route::prefix('eventos')->name('eventos.')->group(function () {
        Route::get('/', [EventosController::class, 'index'])->name('index');
        Route::get('/listar', [EventosController::class, 'listar'])->name('listar');
        Route::get('/recientes', [EventosController::class, 'recientes'])->name('recientes');
        Route::get('/{id}', [EventosController::class, 'show'])->name('show');
        Route::post('/exportar', [EventosController::class, 'exportar'])->name('exportar');
    });

    // Personas (para búsquedas y detalles)
    Route::prefix('personas')->name('personas.')->group(function () {
        Route::post('/buscar', [CapturaController::class, 'buscarPersonas'])->name('buscar');
        Route::get('/{codigo}', [CapturaController::class, 'detallesPersona'])->name('detalles');
    });

    // Huellas (para gestión)
    Route::prefix('huellas')->name('huellas.')->group(function () {
        Route::post('/registrar', [CapturaController::class, 'registrarHuella'])->name('registrar');
        Route::get('/persona/{codigo}', [CapturaController::class, 'huellasPersona'])->name('persona');
    });
});

// API Routes (sin middleware web para uso con dispositivos externos)
Route::prefix('api/huellero')->name('api.huellero.')->group(function () {

    // Verificación automática (para integración con hardware)
    Route::post('/verificar', [VerificacionController::class, 'verificarHuella'])->name('verificar');

    // Eventos de hardware (para notificaciones desde JavaScript)
    Route::post('/evento-hardware', function(\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info('Evento de hardware recibido', $request->all());
        return response()->json(['success' => true]);
    })->name('evento_hardware');

    Route::post('/evento-captura', function(\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::info('Evento de captura recibido', $request->all());
        return response()->json(['success' => true]);
    })->name('evento_captura');

    // Estado del sistema (para monitoreo)
    Route::get('/estado', [VerificacionController::class, 'estadoSistema'])->name('estado');

    // Dispositivos conectados
    Route::get('/dispositivos', [DashboardController::class, 'dispositivos'])->name('dispositivos');
});
