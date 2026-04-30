<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Api\IntentoOperacionRequest;
use App\Modules\Sarlaft\Services\IntentoOperacionService;
use Illuminate\Http\JsonResponse;

class IntentoOperacionController extends Controller
{
    public function __construct(
        private readonly IntentoOperacionService $service,
    ) {}

    public function store(IntentoOperacionRequest $request): JsonResponse
    {
        $sistema = $request->get('sistema_consumidor');

        $intento = $this->service->registrarPush(
            $request->validated(),
            $sistema,
            $request->ip() ?? 'unknown',
        );

        return response()->json([
            'message' => 'Intento registrado. Se generaron ' . $intento->alertas->count() . ' alerta(s).',
            'intento_id' => $intento->id,
            'alertas_generadas' => $intento->alertas->count(),
        ], 201);
    }
}

