<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\PrsTipoBloqueo;
use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\CopBloqueoHandlerContract;
use App\Modules\GestionRRHH\Services\DescansosService;
use Illuminate\Support\Collection;

class CopDescansoBloqueoHandler implements CopBloqueoHandlerContract
{
    public function __construct(private readonly DescansosService $descansosService) {}

    public function key(): string
    {
        return 'descanso';
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
        $ultimoEvento = DescansosService::obtenerResumenUltimoEventoDescanso($identificacion);

        $camposPreparados = $campos->map(function ($campo) use ($ultimoEvento) {
            $campo->options = $campo->opciones_json ?? [];

            if ($campo->fuente_opciones === 'descanso_eventos_levantamiento') {
                $campo->options = $this->descansosService->obtenerOpcionesLevantamientoDescanso(
                    $ultimoEvento['evento_codigo'] ?? null
                );
            }

            return $campo;
        });

        return [
            'campos' => $camposPreparados,
            'contexto' => [
                'ultimo_evento' => $ultimoEvento,
            ],
        ];
    }

    public function execute(PrsTipoBloqueo $tipoBloqueo, string $identificacion, array $payload): array
    {
        return $this->descansosService->procesarLevantamientoCop(
            $identificacion,
            $payload,
            $tipoBloqueo->codigo_fics ? (int) $tipoBloqueo->codigo_fics : null,
            $tipoBloqueo->codigo_logtrans ? (int) $tipoBloqueo->codigo_logtrans : null,
        );
    }
}
