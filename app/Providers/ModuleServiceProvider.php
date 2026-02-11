<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Auto-descubre módulos en app/Modules/ y registra:
 * - View namespaces (radfact::, huellero::, etc.)
 * - Migration paths
 * - Route files
 * - Console Commands
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Mapa de nombre de directorio → slug para view namespaces.
     * Ejemplo: view('radfact::index') carga desde app/Modules/RadFact/Resources/Views/index.blade.php
     */
    private const VIEW_SLUGS = [
        'Administration' => 'administration',
        'Configuracion' => 'configuracion',
        'GestionRRHH' => 'gestionrrhh',
        'GestionWeb' => 'gestionweb',
        'Huellero' => 'huellero',
        'PagosRecaudos' => 'pagosrecaudos',
        'RadFact' => 'radfact',
        'SIG' => 'sig',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        $modules = array_filter(scandir($modulesPath), function (string $dir) use ($modulesPath) {
            return $dir !== '.' && $dir !== '..' && is_dir($modulesPath.'/'.$dir);
        });

        foreach ($modules as $module) {
            $basePath = $modulesPath.'/'.$module;
            $slug = self::VIEW_SLUGS[$module] ?? strtolower($module);

            $this->registerViews($basePath, $slug);
            $this->registerMigrations($basePath);
            $this->registerRoutes($basePath);
            $this->registerCommands($basePath, $module);
        }
    }

    private function registerViews(string $basePath, string $slug): void
    {
        $viewsPath = $basePath.'/Resources/Views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $slug);
        }
    }

    private function registerMigrations(string $basePath): void
    {
        $migrationsPath = $basePath.'/Database/Migrations';

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    private function registerRoutes(string $basePath): void
    {
        $routeFile = $basePath.'/Routes/web.php';

        if (file_exists($routeFile)) {
            Route::middleware('web')->group($routeFile);
        }
    }

    private function registerCommands(string $basePath, string $module): void
    {
        $commandsPath = $basePath.'/Console/Commands';

        if (is_dir($commandsPath) && $this->app->runningInConsole()) {
            $namespace = "App\\Modules\\{$module}\\Console\\Commands";
            $commands = [];

            foreach (glob($commandsPath.'/*.php') as $file) {
                $className = $namespace.'\\'.pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($className)) {
                    $commands[] = $className;
                }
            }

            if (! empty($commands)) {
                $this->commands($commands);
            }
        }
    }
}
