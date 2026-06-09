<?php

namespace App\Modules\GestionRRHH\Services\Novedades\Descargos;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DescargoCitacionService
{
    private const CONNECTION = 'oracle-360';
    private const ESTADO_CITADO_DESCARGOS = 'CITADO_DESCARGOS';

    public function crearCitacion(
        array $payload,
        string $documentoActor,
        string $origen = 'WEB',
        string $ipEquipo = '',
        string $sistemaOrigen = 'AUTOGESTION'
    ): array {
        $documentoPersona = trim((string) ($payload['documento_persona'] ?? ''));
        $documentoActor = trim($documentoActor);

        if ($documentoPersona === '') {
            return [
                'ok' => false,
                'message' => 'El documento de la persona es obligatorio.',
            ];
        }

        if ($documentoActor === '') {
            return [
                'ok' => false,
                'message' => 'No fue posible identificar el actor que registra la citacion.',
            ];
        }

        try {
            $fechaCitacion = Carbon::parse((string) ($payload['fecha_citacion'] ?? ''));
        } catch (\Throwable) {
            return [
                'ok' => false,
                'message' => 'La fecha de citacion no es valida.',
            ];
        }

        $id = Str::lower((string) Str::uuid());
        $ahora = now();

        try {
            DB::connection(self::CONNECTION)
                ->table('EMP_DESCARGOS_CITACIONES')
                ->insert([
                    'id' => $id,
                    'documento_persona' => $documentoPersona,
                    'fecha_citacion' => $fechaCitacion->format('Y-m-d H:i:s'),
                    'observacion' => $this->normalizarNullable($payload['observacion'] ?? null),
                    'estado' => self::ESTADO_CITADO_DESCARGOS,
                    'fecha_notificacion' => null,
                    'origen' => $this->normalizarNullable($origen),
                    'ip_equipo' => $this->normalizarNullable($ipEquipo),
                    'sistema_origen' => $this->normalizarNullable($sistemaOrigen),
                    'id_creacion' => $documentoActor,
                    'id_modifica' => $documentoActor,
                    'fecha_creacion' => $ahora->format('Y-m-d H:i:s'),
                    'fecha_modifica' => $ahora->format('Y-m-d H:i:s'),
                ]);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible registrar la citacion a descargos.',
                'error' => $e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'id' => $id,
            'estado' => self::ESTADO_CITADO_DESCARGOS,
        ];
    }

    public function marcarFechaNotificacion(string $idCitacion): void
    {
        $idCitacion = trim($idCitacion);
        if ($idCitacion === '') {
            return;
        }

        DB::connection(self::CONNECTION)
            ->table('EMP_DESCARGOS_CITACIONES')
            ->where('id', $idCitacion)
            ->update([
                'fecha_notificacion' => now()->format('Y-m-d H:i:s'),
                'fecha_modifica' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    private function normalizarNullable(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
