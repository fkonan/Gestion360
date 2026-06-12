<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentalStorageService
{
    private ?FilesystemAdapter $disk = null;
    private ?string $diskName = null;
    private bool $configValidated = false;

    public function guardarArchivo(
        UploadedFile $archivo,
        string $documento,
        string $categoria,
        string|int $radicado,
        string $nombreBase,
        ?int $year = null
    ): array {
        $this->validarConfiguracion();

        $relativePath = $this->construirRutaRelativa(
            documento: $documento,
            categoria: $categoria,
            radicado: $radicado,
            nombreBase: $nombreBase,
            extension: $archivo->getClientOriginalExtension(),
            year: $year
        );

        $disk = $this->obtenerDisco();
        $stream = fopen($archivo->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('No fue posible abrir el archivo temporal para carga documental.');
        }

        try {
            $guardado = $disk->writeStream($relativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $guardado) {
            throw new RuntimeException('No fue posible guardar el archivo en el repositorio documental SFTP.');
        }

        return [
            'relative_path' => $relativePath,
            'public_url' => $this->construirUrlPublica($relativePath),
            'filename' => basename($relativePath),
        ];
    }

    public function construirRutaRelativa(
        string $documento,
        string $categoria,
        string|int $radicado,
        string $nombreBase,
        ?string $extension = null,
        ?int $year = null
    ): string {
        $documento = trim($documento);
        $categoria = Str::upper(trim($categoria));
        $radicado = trim((string) $radicado);

        if ($documento === '' || $categoria === '' || $radicado === '') {
            throw new RuntimeException('No fue posible construir la ruta documental por datos incompletos.');
        }

        $baseDirectory = $this->obtenerBaseDirectoryConfigurada();
        $year = $year ?: (int) now()->format('Y');
        $extension = $this->normalizarExtension($extension);
        $nombreArchivo = $this->normalizarNombreBase($nombreBase);

        $segmentos = array_filter([
            $baseDirectory,
            preg_replace('/[^0-9A-Za-z_-]/', '', $documento),
            preg_replace('/[^0-9A-Z_-]/', '', $categoria),
            (string) $year,
            preg_replace('/[^0-9A-Za-z_-]/', '', $radicado),
        ], fn ($segmento) => $segmento !== '');

        return implode('/', $segmentos).'/'.$nombreArchivo.'.'.$extension;
    }

    public function construirUrlPublica(string $ruta): ?string
    {
        $ruta = trim($ruta);
        if ($ruta === '') {
            return null;
        }

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return $ruta;
        }

        $baseUrl = $this->obtenerPublicBaseUrlConfigurada();
        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/'.ltrim($ruta, '/');
    }

    public function resolverRutaStorage(string $ruta): ?string
    {
        $ruta = trim($ruta);
        if ($ruta === '') {
            return null;
        }

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($ruta, PHP_URL_PATH), '/');

            return $path !== '' ? $path : null;
        }

        return ltrim($ruta, '/');
    }

    public function abrirStream(string $ruta)
    {
        $this->validarConfiguracion();

        $storagePath = $this->resolverRutaStorage($ruta);
        if ($storagePath === null) {
            throw new RuntimeException('La ruta documental no es valida.');
        }

        $stream = $this->obtenerDisco()->readStream($storagePath);
        if (! is_resource($stream)) {
            throw new RuntimeException('No fue posible abrir el archivo documental.');
        }

        return $stream;
    }

    public function obtenerMimeType(string $ruta): string
    {
        $this->validarConfiguracion();

        $storagePath = $this->resolverRutaStorage($ruta);
        if ($storagePath === null) {
            return 'application/octet-stream';
        }

        try {
            $mimeType = $this->obtenerDisco()->mimeType($storagePath);

            return is_string($mimeType) && $mimeType !== '' ? $mimeType : 'application/octet-stream';
        } catch (\Throwable) {
            return 'application/octet-stream';
        }
    }

    public function obtenerNombreArchivo(string $ruta): string
    {
        $storagePath = $this->resolverRutaStorage($ruta);
        if ($storagePath === null) {
            return 'documento';
        }

        return basename($storagePath);
    }

    public function obtenerDiscoConfigurado(): string
    {
        return trim((string) config('services.documental.disk', 'documental_sftp'));
    }

    public function obtenerBaseDirectoryConfigurada(): string
    {
        return $this->obtenerConfiguracionDocumentalActiva()['base_directory'];
    }

    public function obtenerPublicBaseUrlConfigurada(): string
    {
        return $this->obtenerConfiguracionDocumentalActiva()['public_base_url'];
    }

    public function eliminarSiExiste(?string $ruta): void
    {
        $ruta = trim((string) $ruta);
        if ($ruta === '' || filter_var($ruta, FILTER_VALIDATE_URL)) {
            return;
        }

        $disk = $this->obtenerDisco();
        if ($disk->exists($ruta)) {
            $disk->delete($ruta);
        }
    }

    public function eliminarMultiplesSiExisten(array $rutas): void
    {
        foreach ($rutas as $ruta) {
            try {
                $this->eliminarSiExiste(is_scalar($ruta) ? (string) $ruta : null);
            } catch (\Throwable) {
                // Evita ocultar el error principal de la operacion llamadora.
                continue;
            }
        }
    }

    private function normalizarNombreBase(string $nombreBase): string
    {
        $nombreBase = Str::of($nombreBase)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

        return $nombreBase !== '' ? $nombreBase : 'documento';
    }

    private function normalizarExtension(?string $extension): string
    {
        $extension = Str::of((string) $extension)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->value();

        return $extension !== '' ? $extension : 'bin';
    }

    private function obtenerConfiguracionDocumentalActiva(): array
    {
        $diskName = $this->obtenerDiscoConfigurado();
        $baseDirectory = trim((string) config('services.documental.base_directory', 'ArchivoDigital'));
        $publicBaseUrl = trim((string) config('services.documental.public_base_url', ''));
        $overrideConfig = (array) config('services.documental.overrides.'.$diskName, []);

        $overrideBaseDirectory = trim((string) ($overrideConfig['base_directory'] ?? ''));
        if ($overrideBaseDirectory !== '') {
            $baseDirectory = $overrideBaseDirectory;
        }

        $overridePublicBaseUrl = trim((string) ($overrideConfig['public_base_url'] ?? ''));
        if ($overridePublicBaseUrl !== '') {
            $publicBaseUrl = $overridePublicBaseUrl;
        }

        return [
            'base_directory' => trim($baseDirectory, '/'),
            'public_base_url' => rtrim($publicBaseUrl, '/'),
        ];
    }

    private function validarConfiguracion(): void
    {
        if ($this->configValidated) {
            return;
        }

        $host = trim((string) config('filesystems.disks.'.$this->obtenerDiscoConfigurado().'.host', ''));
        $username = trim((string) config('filesystems.disks.'.$this->obtenerDiscoConfigurado().'.username', ''));
        $password = trim((string) config('filesystems.disks.'.$this->obtenerDiscoConfigurado().'.password', ''));
        $privateKey = trim((string) config('filesystems.disks.'.$this->obtenerDiscoConfigurado().'.privateKey', ''));

        if ($host === '') {
            throw new RuntimeException('No esta configurado DOCUMENTAL_SFTP_HOST.');
        }

        if ($username === '') {
            throw new RuntimeException('No esta configurado DOCUMENTAL_SFTP_USERNAME.');
        }

        if ($password === '' && $privateKey === '') {
            throw new RuntimeException('Debes configurar DOCUMENTAL_SFTP_PASSWORD o DOCUMENTAL_SFTP_PRIVATE_KEY.');
        }

        $this->configValidated = true;
    }

    private function obtenerDisco(): FilesystemAdapter
    {
        $this->validarConfiguracion();

        $diskName = $this->obtenerDiscoConfigurado();
        if ($this->disk === null || $this->diskName !== $diskName) {
            $this->disk = Storage::disk($diskName);
            $this->diskName = $diskName;
        }

        return $this->disk;
    }
}
