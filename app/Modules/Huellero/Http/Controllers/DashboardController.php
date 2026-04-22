<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Huellero\Services\DigitalPersonaService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(private readonly DigitalPersonaService $digitalPersonaService)
    {
    }

    /**
     * Obtener dispositivos conectados.
     */
    public function dispositivos()
    {
        try {
            $dispositivos = $this->digitalPersonaService->getConnectedDevices();

            return response()->json([
                'success' => true,
                'dispositivos' => $dispositivos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo dispositivos', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
