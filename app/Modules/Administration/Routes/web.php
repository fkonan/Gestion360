<?php

use App\Constants\Permisos;
use App\Modules\Administration\Http\Controllers\GestionPasajesController;
use App\Modules\Administration\Http\Controllers\PermisosController;
use App\Modules\Administration\Http\Controllers\PersonaController;
use App\Modules\Administration\Http\Controllers\ReportesController;
use App\Modules\Administration\Http\Controllers\RolController;
use App\Modules\Administration\Http\Controllers\TiquetesImpresosController;
use App\Modules\Administration\Http\Controllers\UserController;
use App\Modules\GestionRRHH\Http\Controllers\ConductorController;
use App\Modules\GestionRRHH\Http\Controllers\PoliticasController;
use Illuminate\Support\Facades\Route;

// Rutas Modulo Gestion Pasajes
Route::prefix('gestionPasajes')->middleware(['auth', 'permisos:'.Permisos::GESTION_PASAJES_ACCEDER, 'modulo.activo:13'])->group(function () {
    Route::prefix('buscar-viaje')->middleware('submodulo.activo:23')->group(function () {
        Route::get('/', [GestionPasajesController::class, 'formBuscarViaje'])->name('buscar-viaje.index');
        Route::post('/filtrar', [GestionPasajesController::class, 'filtrarViajes'])->name('buscar-viaje.filtrar');
    });
});

// Impresion tiquetes gestion pasajes
Route::get('/imprimir-tiquete/{id}', [GestionPasajesController::class, 'imprimirTiquetes'])->name('imprimir-tiquetes');

// Ruta Modulo administración
Route::prefix('administracion')->middleware(['auth', 'permisos:'.Permisos::ADMINISTRACION_ACCEDER, 'modulo.activo:12'])->group(function () {

    // Submodulo Personas
    Route::prefix('personas')->middleware(['permisos:administracion.personas.acceder', 'submodulo.activo:21'])->group(function () {
        Route::get('/', [PersonaController::class, 'index'])->name('personas.index');
        Route::get('/crear', [PersonaController::class, 'create'])->name('personas.create');
        Route::post('/', [PersonaController::class, 'store'])->name('personas.store');
        Route::get('/{id}/edit', [PersonaController::class, 'edit'])->name('personas.edit');
        Route::put('/{id}', [PersonaController::class, 'update'])->name('personas.update');
        Route::get('cargarDatos', [PersonaController::class, 'cargarDatos'])->middleware('soloAJAX')->name('personas.cargarDatos');
        Route::post('/{id}/cambiar-estado', [PersonaController::class, 'cambiarEstado'])->middleware('soloAJAX')->name('personas.cambiarEstado');
    });

    // Submodulo Usuarios
    Route::prefix('usuarios')->middleware(['permisos:'.Permisos::ADMINISTRACION_USUARIOS_ACCEDER, 'submodulo.activo:20'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/create', [UserController::class, 'create'])->middleware('soloAJAX')->name('usuarios.create');
        Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->middleware('soloAJAX')->name('usuarios.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('usuarios.update');
        Route::get('cargarDatos', [UserController::class, 'cargarDatos'])->middleware('soloAJAX')->name('usuarios.cargarDatos');
        Route::get('/{id}/roles', [RolController::class, 'editRolUsuario'])->middleware('soloAJAX')->name('roles.edit');
        Route::put('/{id}/roles', [RolController::class, 'updateRolUsuario'])->name('roles.update');
        Route::get('/{id}/permisos', [PermisosController::class, 'edit'])->middleware('soloAJAX')->name('permisos.edit');
        Route::put('/{id}/permisos', [PermisosController::class, 'update'])->name('permisos.update');
        Route::post('/{id}/cambiar-estado', [UserController::class, 'cambiarEstado'])->middleware('soloAJAX')->name('usuarios.cambiarEstado');
    });

    // Submodulo Reportes
    Route::prefix('reporteador')->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_ACCEDER, 'submodulo.activo:22'])->group(function () {
        Route::get('/', [ReportesController::class, 'getReportes'])->name('reportes.index');

        Route::get('/reportes', [ReportesController::class, 'show'])->name('reportes.show');
        Route::get('/reportes/{area}', [ReportesController::class, 'reportesPorArea'])->name('reportes.area');
        Route::get('/{id}/formulario', [ReportesController::class, 'mostrarFormulario'])->name('reportes.formulario');
        Route::get('/api/reportes', [ReportesController::class, 'data'])->name('reportes.data');
        Route::get('/api/reportes/exportar-csv', [ReportesController::class, 'exportarCsv'])->name('reportes.exportarCsv');

        // Reportes Personas
        Route::prefix('personas')->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_EMPLEADOS])->group(function () {
            Route::get('/', [ReportesController::class, 'reportesPersonas'])->name('reportes.personas');

            // Actualizacion datos empleados y conductores
            Route::get('/actualizacion-datos', [ReportesController::class, 'reporteActualizacionDatos'])->name('empleados.actDatos');
            Route::post('/actualizacion-datos/filtrar', [ReportesController::class, 'filtrarActualizacionDatos'])->name('filtrar.actDatos');
            Route::get('/actualizacion-datos/listaActualizacionDatos', [ReportesController::class, 'listaActualizacionDatos'])->name('lista.actDatos');
            Route::get('/actualizacion-datos/cargarData', [ReportesController::class, 'cargarDataActualizacionDatos'])->middleware('soloAJAX')->name('actDatos.cargarData');
            Route::get('/actualizacion-datos/firmas/descargar', [PoliticasController::class, 'generarPDFDatosActualizacion'])->name('decargar.pdf.actualizacionDatos');

            // Firma politicas empleados
            Route::get('/firmas-politicas', [ReportesController::class, 'reporteFirmaPoliticas'])->name('empleados.firmaPoliticas');
            Route::post('/firmas-politicas/filtrar', [ReportesController::class, 'filtrarfirmaPoliticas'])->name('filtrar.firmaPoliticas');
            Route::get('/firmas-politicas/listaFirmaPoliticas', [ReportesController::class, 'listaFirmasPoliticas'])->name('lista.firmaPoliticas');
            Route::get('/firmas-politicas/cargarData', [ReportesController::class, 'cargarDataFirmaPoliticas'])->middleware('soloAJAX')->name('firmaPoliticas.cargarData');
            Route::get('/firmas-politicas/{identificacion}/comprobante', [ReportesController::class, 'generarComprobantePDF'])->name('firmaNormas.comprobantePDF');
        });

        // Reportes Conductores
        Route::prefix('conductores')->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_CONDUCTORES])->group(function () {
            Route::get('/', [ReportesController::class, 'reportesConductores'])->name('reportes.conductores');

            // Actualizacion estado conductor FICS
            Route::get('/actualizarEstadoModal', [ConductorController::class, 'formActualizarEstado'])->middleware('soloAJAX')->name('conductor.estado');
            Route::put('/actualizarEstado', [ConductorController::class, 'actualizarEstadoConductor'])->name('conductor.actualizarEstado');

            // Novedades preoperacionales COP
            Route::get('/reporte-preoperacionales', [ConductorController::class, 'reportePreoperacionales'])->name('conductor.preoperacional.reporte');
            Route::post('/reporte-preoperacionales', [ConductorController::class, 'filtrarPreoperacionales'])->name('filtrar.preoperacionales');
            Route::get('/preoperacion-archivo/{id}', [ConductorController::class, 'preoperacionArchivo'])->name('conductor.preoperacional.archivo');
        });

        // Reportes Pasajes
        Route::prefix('pasajes')->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_PASAJES])->group(function () {

            Route::get('/', [ReportesController::class, 'reportesPasajes'])->name('reportes.pasajes');

            // Impresion tiquetes
            Route::get('/impresionTiquetes', [TiquetesImpresosController::class, 'fechasReporte'])->middleware('soloAJAX')->name('reportes.tiquetes');
            Route::post('/impresionTiquetes/filtrar', [TiquetesImpresosController::class, 'filtrarTiquetes'])->name('reportes.filtrarTiquetes');
            Route::get('/impresionTiquetes/listaTiquetes', [TiquetesImpresosController::class, 'listaTiquetes'])->name('reportes.listaTiquetes');
            Route::get('/impresionTiquetes/cargarData', [TiquetesImpresosController::class, 'cargarDataTiquetes'])->middleware('soloAJAX')->name('reportes.cargarData');

            // Esquema tarifario pasajes
            Route::get('/esquemaTarifarioPasajes', [GestionPasajesController::class, 'formEsquemaTarifario'])->name('esquemaTarifario.index');
            Route::post('/esquemaTarifarioPasajes/filtrar', [GestionPasajesController::class, 'filtrarEsquemaTarifario'])->name('esquemaTarifario.filtrar');
            Route::get('/esquemaTarifarioPasajes/listaDatos', [GestionPasajesController::class, 'listaEsquemaTarifario'])->name('esquemaTarifario.listaDatos');
            Route::get('/esquemaTarifarioPasajes/cargarData', [GestionPasajesController::class, 'cargarDataEsquemaTarifario'])->name('esquemaTarifario.cargarData');
        });

        // Reportes Carga
        Route::prefix('carga')->group(function () {
            Route::get('/', [ReportesController::class, 'reportesCarga'])->name('reportes.carga');
        });

    });
});
