# Arquitectura SARLAFT en Autogestion2

## Namespace
- `App\\Modules\\Sarlaft\\...`

## Capas
- Controllers: `Http/Controllers/Admin` y `Http/Controllers/Api`.
- Requests: `Http/Requests/Admin|Api`.
- Resources: `Http/Resources`.
- Services: `Services`.
- Events/Listeners: `Events`, `Listeners`.
- Jobs/Commands: `Jobs`, `Console/Commands`.
- Vistas: `Resources/Views` (namespace blade: `sarlaft::`).

## Integraciones core
- Rutas web: `app/Modules/Sarlaft/Routes/web.php` (autoload por `ModuleServiceProvider`).
- Rutas api: `app/Modules/Sarlaft/Routes/api.php` (incluidas en `routes/api.php`).
- Scheduler: `routes/console.php` -> `listas:sincronizar` diario 02:00.
- Event listeners: registrados en `app/Providers/AppServiceProvider.php`.
