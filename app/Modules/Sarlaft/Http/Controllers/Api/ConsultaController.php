<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Api\ConsultaLoteRequest;
use App\Modules\Sarlaft\Http\Requests\Api\ConsultaRequest;
use App\Modules\Sarlaft\Http\Resources\ConsultaResource;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Services\ConsultaService;
use Illuminate\Http\JsonResponse;

class ConsultaController extends Controller
{
    public function __construct(
        private readonly ConsultaService $consultaService,
    ) {}

    public function store(ConsultaRequest $request): ConsultaResource
    {
        $sistema = $request->get('sistema_consumidor');

        $resultado = $this->consultaService->ejecutar(
            $request->validated(),
            $request->ip() ?? 'unknown',
            $sistema->codigo ?? 'api',
        );

        return new ConsultaResource($resultado);
    }

    public function storeLote(ConsultaLoteRequest $request): JsonResponse
    {
        $sistema = $request->get('sistema_consumidor');

        $resultados = $this->consultaService->ejecutarLote(
            $request->validated('registros'),
            $request->ip() ?? 'unknown',
            $sistema->codigo ?? 'api',
        );

        return response()->json([
            'data' => ConsultaResource::collection(collect($resultados)),
            'total' => count($resultados),
        ]);
    }

    public function show(Consulta $consulta): ConsultaResource
    {
        $consulta->load('alertas');

        return new ConsultaResource($consulta);
    }
}
