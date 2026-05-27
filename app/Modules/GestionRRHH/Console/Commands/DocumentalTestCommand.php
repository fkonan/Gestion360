<?php

namespace App\Modules\GestionRRHH\Console\Commands;

use App\Services\DocumentalStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentalTestCommand extends Command
{
    protected $signature = 'documental:test
                            {ruta? : Ruta relativa a validar en el servidor documental}
                            {--read : Intenta abrir el archivo indicado por la ruta}';

    protected $description = 'Prueba conectividad y acceso al repositorio documental configurado por SFTP.';

    public function handle(DocumentalStorageService $documentalStorageService): int
    {
        $diskName = $documentalStorageService->obtenerDiscoConfigurado();
        $defaultPath = trim((string) config('services.documental.base_directory', 'ArchivoDigital'), '/');
        $path = trim((string) ($this->argument('ruta') ?: $defaultPath), '/');
        $diskConfig = (array) config('filesystems.disks.'.$diskName, []);

        $this->components->info('Probando repositorio documental');
        $this->newLine();
        $this->line('Disco: <info>'.$diskName.'</info>');
        $this->line('Host: <info>'.($diskConfig['host'] ?? 'N/D').'</info>');
        $this->line('Puerto: <info>'.($diskConfig['port'] ?? 'N/D').'</info>');
        $this->line('Root: <info>'.($diskConfig['root'] ?? 'N/D').'</info>');
        $this->line('Base directory: <info>'.($defaultPath !== '' ? $defaultPath : '/').'</info>');
        $this->line('Base URL: <info>'.((string) config('services.documental.public_base_url', 'N/D')).'</info>');
        $this->line('Ruta a validar: <info>'.($path !== '' ? $path : '/').'</info>');
        $this->newLine();

        try {
            $documentalStorageService->obtenerMimeType($path);
            $exists = Storage::disk($diskName)->exists($path);
        } catch (Throwable $exception) {
            $this->components->error('Fallo la conexion o el acceso al repositorio documental.');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $exists) {
            $this->components->warn('Hay conexion al servidor documental, pero la ruta consultada no existe.');

            return self::SUCCESS;
        }

        $this->components->info('Conexion y acceso al repositorio documental confirmados.');

        if (! $this->option('read')) {
            return self::SUCCESS;
        }

        try {
            $stream = $documentalStorageService->abrirStream($path);
            $firstBytes = fread($stream, 32);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->components->info('Lectura del archivo confirmada.');
            $this->line('Bytes leidos: <info>'.strlen((string) $firstBytes).'</info>');
        } catch (Throwable $exception) {
            $this->components->error('La ruta existe, pero no fue posible abrir el archivo.');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
