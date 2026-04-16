<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertaEvidenciaService
{
    private const DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return array<int, array<string, mixed>>
     */
    public function guardarArchivos(Alerta $alerta, array $archivos, ?int $userId): array
    {
        $evidencias = [];

        foreach ($archivos as $archivo) {
            if (! $archivo instanceof UploadedFile) {
                continue;
            }

            $evidenciaId = (string) Str::uuid();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $nombreAlmacenado = $evidenciaId.($extension !== '' ? '.'.$extension : '');
            $ruta = $archivo->storeAs($this->directorio($alerta), $nombreAlmacenado, self::DISK);

            $evidencias[] = [
                'id' => $evidenciaId,
                'disk' => self::DISK,
                'path' => $ruta,
                'original_name' => trim($archivo->getClientOriginalName()) !== ''
                    ? $archivo->getClientOriginalName()
                    : $nombreAlmacenado,
                'mime_type' => $archivo->getClientMimeType(),
                'size_bytes' => (int) ($archivo->getSize() ?? 0),
                'uploaded_by' => $userId,
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        return $evidencias;
    }

    /**
     * @param  array<int, array<string, mixed>>  $evidencias
     */
    public function eliminarArchivos(array $evidencias): void
    {
        foreach ($evidencias as $evidencia) {
            $disk = isset($evidencia['disk']) && is_string($evidencia['disk']) ? $evidencia['disk'] : self::DISK;
            $ruta = isset($evidencia['path']) && is_string($evidencia['path']) ? $evidencia['path'] : null;

            if ($ruta === null || $ruta === '') {
                continue;
            }

            Storage::disk($disk)->delete($ruta);
        }
    }

    public function descargar(Alerta $alerta, string $evidenciaId): StreamedResponse
    {
        $evidencia = $this->buscarEvidencia($alerta, $evidenciaId);

        abort_if($evidencia === null, 404);

        $disk = isset($evidencia['disk']) && is_string($evidencia['disk']) ? $evidencia['disk'] : self::DISK;
        $ruta = isset($evidencia['path']) && is_string($evidencia['path']) ? $evidencia['path'] : null;

        abort_if($ruta === null || ! Storage::disk($disk)->exists($ruta), 404);

        $nombreDescarga = isset($evidencia['original_name']) && is_string($evidencia['original_name'])
            ? $evidencia['original_name']
            : basename($ruta);

        return Storage::disk($disk)->download($ruta, $nombreDescarga);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarEvidencia(Alerta $alerta, string $evidenciaId): ?array
    {
        $evidencias = is_array($alerta->evidencias) ? $alerta->evidencias : [];

        foreach ($evidencias as $evidencia) {
            if (! is_array($evidencia)) {
                continue;
            }

            if (($evidencia['id'] ?? null) === $evidenciaId) {
                return $evidencia;
            }
        }

        return null;
    }

    private function directorio(Alerta $alerta): string
    {
        return 'sarlaft/alertas/'.$alerta->id.'/evidencias';
    }
}
