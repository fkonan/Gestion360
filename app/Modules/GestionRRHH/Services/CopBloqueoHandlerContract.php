<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\PrsTipoBloqueo;
use Illuminate\Support\Collection;

interface CopBloqueoHandlerContract
{
    public function key(): string;

    public function detect(PrsTipoBloqueo $tipoBloqueo, string $identificacion): array;

    public function preparePresentation(
        PrsTipoBloqueo $tipoBloqueo,
        string $identificacion,
        Collection $campos,
        array $estadoBloqueo
    ): array;

    public function execute(PrsTipoBloqueo $tipoBloqueo, string $identificacion, array $payload): array;
}
