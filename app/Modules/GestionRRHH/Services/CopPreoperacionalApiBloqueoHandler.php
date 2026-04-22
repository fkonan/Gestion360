<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\Preoperacionales;
use App\Modules\GestionRRHH\Models\PrsTipoBloqueo;
use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\CopBloqueoHandlerContract;
use Illuminate\Support\Collection;

class CopPreoperacionalApiBloqueoHandler implements CopBloqueoHandlerContract
{
    public function key(): string
    {
        return 'preoperacional_api';
    }

    public function detect(PrsTipoBloqueo $tipoBloqueo, string $identificacion): array
    {
        return BloqueoService::obtenerEstadoBloqueo(
            $identificacion,
            $tipoBloqueo->codigo_fics,
            $tipoBloqueo->codigo_logtrans
        );
    }

    public function preparePresentation(
        PrsTipoBloqueo $tipoBloqueo,
        string $identificacion,
        Collection $campos,
        array $estadoBloqueo
    ): array {
        $camposPreparados = $campos->map(function ($campo) {
            $campo->options = $campo->opciones_json ?? [];

            return $campo;
        });

        return [
            'campos' => $camposPreparados,
            'contexto' => [],
        ];
    }

    public function execute(PrsTipoBloqueo $tipoBloqueo, string $identificacion, array $payload): array
    {
        $estadoInicial = BloqueoService::obtenerEstadoBloqueo(
            $identificacion,
            $tipoBloqueo->codigo_fics,
            $tipoBloqueo->codigo_logtrans
        );

        if (! ($estadoInicial['bloqueado'] ?? false)) {
            return [
                'success' => false,
                'status' => 'no_blocks',
                'message' => 'El conductor no presenta bloqueos activos de preoperacional en FICS ni en Logtrans.',
                'estado_inicial' => $estadoInicial,
            ];
        }

        $resultadoDesbloqueo = BloqueoService::levantarBloqueoPreoperacional($identificacion);
        $estadoDesbloqueo = $resultadoDesbloqueo['status'] ?? 'error';
        $mensajeDesbloqueo = $resultadoDesbloqueo['message'] ?? null;

        if ($estadoDesbloqueo === 'unlocked') {
            $observacion = trim((string) ($payload['observacion'] ?? ''));

            $preoperacional = new Preoperacionales;
            $preoperacional->documento = $identificacion;
            $preoperacional->observacion = $observacion !== '' ? $observacion : null;
            $preoperacional->mensaje_api = $mensajeDesbloqueo;
            $preoperacional->save();

            return [
                'success' => true,
                'status' => 'success',
                'message' => $mensajeDesbloqueo ?: 'Se levanto el bloqueo correctamente.',
                'estado_inicial' => $estadoInicial,
            ];
        }

        if ($estadoDesbloqueo === 'no_blocks') {
            return [
                'success' => false,
                'status' => 'no_blocks',
                'message' => $mensajeDesbloqueo ?: 'El conductor no presenta ningun bloqueo activo.',
                'estado_inicial' => $estadoInicial,
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $mensajeDesbloqueo ?: 'No fue posible levantar el bloqueo de preoperacional.',
            'estado_inicial' => $estadoInicial,
        ];
    }
}
