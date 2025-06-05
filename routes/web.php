<?php

use App\Http\Controllers\ConductorController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GestionPasajesController;
use App\Http\Controllers\GestionWebController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeguimientoIncapacidadController;
use App\Http\Controllers\SubModuloController;
use App\Http\Controllers\TiquetesImpresosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::get('/clear', function () {
    Artisan::call('storage:link');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:cache');
    return "Cleared!";
});
 
//Ruta Modulo administración
Route::prefix("administracion")->middleware(['auth', 'permisos:administracion.acceder','modulo.activo:12'])->group(function(){
    Route::prefix("personas")->middleware(['permisos:administracion.personas.acceder','submodulo.activo:21'])->group(function(){
        Route::get("/",[PersonaController::class,"index"])->name("personas.index");
        Route::get("/crear",[PersonaController::class,"create"])->name("personas.create");
        Route::post("/",[PersonaController::class,"store"])->name("personas.store");
        Route::get("/{id}/edit",[PersonaController::class,"edit"])->name("personas.edit");
        Route::put("/{id}",[PersonaController::class,"update"])->name("personas.update");
        Route::get("cargarDatos",[PersonaController::class,"cargarDatos"])->middleware('soloAJAX')->name("personas.cargarDatos");
        Route::post("/{id}/cambiar-estado", [PersonaController::class, "cambiarEstado"])->middleware('soloAJAX')->name("personas.cambiarEstado");
    });
    Route::prefix("usuarios")->middleware(['permisos:administracion.usuarios.acceder','submodulo.activo:20'])->group(function(){
        Route::get("/",[UserController::class,"index"])->name("usuarios.index");
        Route::get("/create",[UserController::class,"create"])->middleware('soloAJAX')->name("usuarios.create");
        Route::post("/",[UserController::class,"store"])->name("usuarios.store");
        Route::get("/{id}/edit",[UserController::class,"edit"])->middleware('soloAJAX')->name("usuarios.edit");
        Route::put("/{id}",[UserController::class,"update"])->name("usuarios.update");
        Route::get("cargarDatos",[UserController::class,"cargarDatos"])->middleware('soloAJAX')->name("usuarios.cargarDatos");
        Route::get("/{id}/roles",[RolController::class,"editRolUsuario"])->middleware('soloAJAX')->name("roles.edit");
        Route::put("/{id}/roles",[RolController::class,"updateRolUsuario"])->name("roles.update");
        Route::get("/{id}/permisos",[PermisosController::class,"edit"])->middleware('soloAJAX')->name("permisos.edit");
        Route::put("/{id}/permisos",[PermisosController::class,"update"])->name("permisos.update");
        Route::post("/{id}/cambiar-estado", [UserController::class, "cambiarEstado"])->middleware('soloAJAX')->name("usuarios.cambiarEstado");
    });
    Route::prefix("reportes")->middleware(['permisos:administracion.reportes.acceder','submodulo.activo:22'])->group(function(){
        Route::get("/",[ModuloController::class,"getReportes"])->name("reportes.index");

        //Impresion tiquetes
        Route::get("/impresionTiquetes",[TiquetesImpresosController::class,"fechasReporte"])->middleware('soloAJAX')->name("reportes.tiquetes");
        Route::post("/impresionTiquetes/filtrar",[TiquetesImpresosController::class,"filtrarTiquetes"])->name("reportes.filtrarTiquetes");
        Route::get("/impresionTiquetes/listaTiquetes",[TiquetesImpresosController::class,"listaTiquetes"])->name("reportes.listaTiquetes");
        Route::get("/impresionTiquetes/cargarData",[TiquetesImpresosController::class,"cargarDataTiquetes"])->middleware('soloAJAX')->name("reportes.cargarData");

        //Actualizacion estado conductor FICS
        Route::get("/actualizarEstadoModal",[ConductorController::class,"formActualizarEstado"])->middleware('soloAJAX')->name("conductor.estado");
        Route::put("/actualizarEstado",[ConductorController::class,"actualizarEstadoConductor"])->name("conductor.actualizarEstado");

        //Firma politica equipaje
        Route::get("/reporteEquipajeModal",[ConductorController::class,"reporteFirmaEquipaje"])->name("conductor.firmaEquipaje");
        Route::post("/reporteEquipajeModal",[ConductorController::class,"filtrarFirmaEquipaje"])->name("filtrar.firmaEquipaje");
        Route::get("/reporteEquipajeModal/cargarData",[ConductorController::class,"cargarDataFirmaEquipaje"])->middleware('soloAJAX')->name("firmaEquipaje.cargarData");
        Route::get("/reporteEquipajeModal/listaFirmasEquipaje",[ConductorController::class,"listaFirmasEquipaje"])->name("lista.firmaEquipaje");

        //Descanso conductores
        Route::get("/formDescansoConductores",[ConductorController::class,"formDescansoConductores"])->name("conductor.descanso");
        Route::post("/descansoConductores",[ConductorController::class,"registrarEvento"])->name("registrar.evento");

        //Ingreso y salidas conductores
        Route::get("/formIngSalConductores",[ConductorController::class,"formIngSalConductores"])->name("conductor.ingresoSalidas");
        Route::post("/formIngSalConductores/filtrar",[ConductorController::class,"reporteIngSalConductores"])->name("reporte.ingresoSalidas");
        Route::get("/formIngSalConductores/listaDatos",[ConductorController::class,"listaIngSalConductores"])->name("lista.ingresoSalidas");
        Route::get("/formIngSalConductores/cargarData",[ConductorController::class,"cargarDataIngSalConductores"])->name("ingresoSalida.cargarData");

        //Esquema tarifario pasajes
        Route::get("/esquemaTarifarioPasajes",[GestionPasajesController::class,"formEsquemaTarifario"])->name("esquemaTarifario.index");
        Route::post("/esquemaTarifarioPasajes/filtrar",[GestionPasajesController::class,"filtrarEsquemaTarifario"])->name("esquemaTarifario.filtrar");
        Route::get("/esquemaTarifarioPasajes/listaDatos",[GestionPasajesController::class,"listaEsquemaTarifario"])->name("esquemaTarifario.listaDatos");
        Route::get("/esquemaTarifarioPasajes/cargarData",[GestionPasajesController::class,"cargarDataEsquemaTarifario"])->name("esquemaTarifario.cargarData");
    });
});

//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware(['auth', 'permisos:configuracion.acceder','modulo.activo:11'])->group(function(){
    Route::prefix("sistema")->middleware(['permisos:configuracion.gestion_sistema.acceder','submodulo.activo:19'])->group(function(){
        Route::get("/",[ModuloController::class,"getGestionSistema"])->name("gestion-sistema.index");

        Route::prefix("modulos")->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/cargarDatos",[ModuloController::class,"cargarDatos"])->middleware('soloAJAX')->name("cargarDatos");
            Route::get("/create",[ModuloController::class,"create"])->middleware('soloAJAX')->name("create");
            Route::get("/{id}",[ModuloController::class,"edit"])->middleware('soloAJAX')->name("edit");
            Route::post("/",[ModuloController::class,"store"])->name("store");
            Route::put("/{id}",[ModuloController::class,"update"])->name("update");
            Route::post("/{id}/cambiar-estado", [ModuloController::class, "cambiarEstado"])->middleware('soloAJAX')->name("cambiarEstado");
        });

        Route::prefix("submodulos")->name("submodulos.")->group(function(){
            Route::get("/",[SubModuloController::class,"index"])->name("index");
            Route::get("/cargarDatos",[SubModuloController::class,"cargarDatos"])->middleware('soloAJAX')->name("cargarDatos");
            Route::get("/create",[SubModuloController::class,"create"])->middleware('soloAJAX')->name("create");
            Route::post("/",[SubModuloController::class,"store"])->name("store");
            Route::get("/{id}",[SubModuloController::class,"edit"])->middleware('soloAJAX')->name("edit");
            Route::put("/{id}",[SubModuloController::class,"update"])->name("update");
            Route::post("/{id}/cambiar-estado", [SubModuloController::class, "cambiarEstado"])->middleware('soloAJAX')->name("cambiarEstado");
        });

        Route::prefix("roles")->name("roles.")->group(function(){
            Route::get("/",[RolController::class,"index"])->name("index");
            Route::get("/create",[RolController::class,"create"])->name("create");
            Route::post("/",[RolController::class,"store"])->name("store");
            Route::get("/{id}/permisos",[RolController::class,"permisosRol"])->name("permisos");
            Route::put("/{id}/permisos",[RolController::class,"updatePermisos"])->name("permisos.update");
        });
    });
});

//Rutas Modulo Gestion RRHH
Route::prefix("gestionRRHH")->middleware(['auth', 'permisos:gestion_de_rr_hh.acceder','modulo.activo:2'])->group(function(){
    Route::prefix("gestion-empleado")->middleware(['auth', 'permisos:gestion_de_rr_hh.gestion_empleado.acceder','submodulo.activo:7'])->name("gestion-incapacidades.")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionEmpleado"])->name("index");
        Route::get("/incapacidades",[IncapacidadController::class,"listaIncapacidades"])->name("incapacidades");
        Route::get("/incapacidades/cargarDatos",[IncapacidadController::class,"cargarDatos"])->middleware('soloAJAX')->name("incapacidades.cargarDatos");
        Route::get("/incapacidades/{id}/datos",[IncapacidadController::class,"editIncapacidad"])->middleware('soloAJAX')->name("incapacidades.edit");
        Route::put("/incapacidades/{id}/datos",[IncapacidadController::class,"updateIncapacidad"])->name("incapacidades.update");
        Route::get("/incapacidades/{id}/gestion",[IncapacidadController::class,"gestionIncapacidad"])->middleware('soloAJAX')->name("incapacidades.gestion");
        Route::put("/incapacidades/{id}/estado",[IncapacidadController::class,"updateEstadoIncapacidad"])->name("incapacidades.estado");

        //Esta ruta carga adjuntos para la incapacidad y para el seguimiento
        Route::get("/seguimiento/{id}/adjuntos",[IncapacidadController::class,"incapacidadAdjuntos"])->middleware('soloAJAX')->name("seguimiento.adjuntos");
        Route::get("/seguimiento",[SeguimientoIncapacidadController::class,"incapacidadesSeguimiento"])->name("seguimiento");
        Route::get("/seguimiento/cargarDatos",[SeguimientoIncapacidadController::class,"cargarDatosSeguimiento"])->middleware('soloAJAX')->name("seguimiento.cargarDatos");
        Route::get("/seguimiento/{id}/registro",[SeguimientoIncapacidadController::class,"seguimientoDetalle"])->name("seguimiento.detalle");
        Route::get("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"nuevoSeguimiento"])->name("seguimiento.detalle.crear");
        Route::post("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"guardarSeguimiento"])->name("seguimiento.detalle.store");
    });
});

//Rutas Modulo Gestion Pasajes
Route::prefix("gestionPasajes")->middleware(['auth', 'permisos:gestion_pasajes.acceder','modulo.activo:13'])->group(function(){
    Route::prefix("buscar-viaje")->middleware('submodulo.activo:23')->group(function(){
        Route::get("/",[GestionPasajesController::class,"formBuscarViaje"])->name("buscar-viaje.index");
        Route::post("/filtrar",[GestionPasajesController::class,"filtrarViajes"])->name("buscar-viaje.filtrar");
    });
});

//Rutas Modulo Gestion Web
Route::prefix("gestionWeb")->middleware(['auth','permisos:gestion_web.acceder','modulo.activo:14'])->group(function(){ 
    Route::get("/chatbot",[GestionWebController::class,"loginChatBot"])->name("chatbot.index");
});

//Rutas olvido/restablecimiento de contraseña
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.update');

require __DIR__.'/auth.php';


