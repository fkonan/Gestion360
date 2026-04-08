<?php

namespace App\Modules\GestionRRHH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Models\PrsTipoBloqueo;
use App\Modules\GestionRRHH\Services\CopLevantamientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CopLevantamientoController extends Controller
{
    public function index(Request $request, CopLevantamientoService $service)
    {
        $identificacion = trim((string) $request->query('identificacion', ''));
        $searchErrors = [];
        $resultado = [
            'persona' => null,
            'bloqueos' => [],
        ];

        if ($identificacion !== '') {
            $validator = Validator::make(
                ['identificacion' => $identificacion],
                ['identificacion' => ['required', 'regex:/^\d{1,15}$/']],
                [
                    'identificacion.required' => 'La identificacion es obligatoria.',
                    'identificacion.regex' => 'La identificacion no tiene un formato valido.',
                ]
            );

            if ($validator->fails()) {
                $searchErrors = $validator->errors()->toArray();
            } else {
                $resultado = $service->consultarBloqueos($identificacion);
            }
        }

        return view('gestionrrhh::conductores.copLevantamiento', [
            'identificacion' => $identificacion,
            'searchErrors' => $searchErrors,
            'persona' => $resultado['persona'],
            'bloqueos' => $resultado['bloqueos'],
        ]);
    }

    public function ejecutar(
        Request $request,
        PrsTipoBloqueo $tipoBloqueo,
        CopLevantamientoService $service
    ): RedirectResponse {
        $identificacionValidator = Validator::make(
            $request->all(),
            ['identificacion' => ['required', 'regex:/^\d{1,15}$/']],
            [
                'identificacion.required' => 'La identificacion es obligatoria.',
                'identificacion.regex' => 'La identificacion no tiene un formato valido.',
            ]
        );

        if ($identificacionValidator->fails()) {
            return redirect()
                ->route('conductor.levantamiento-cop.index')
                ->withErrors($identificacionValidator, 'bloqueo_'.$tipoBloqueo->id)
                ->withInput();
        }

        $identificacion = trim((string) $request->input('identificacion'));
        $payload = (array) $request->input('campos.'.$tipoBloqueo->id, []);
        $resultado = $service->ejecutar($tipoBloqueo, $identificacion, $payload);
        $redirect = route('conductor.levantamiento-cop.index', [
            'identificacion' => $identificacion,
        ]);

        if (($resultado['status'] ?? null) === 'validation_error') {
            return redirect($redirect)
                ->withErrors($resultado['errors'] ?? [], 'bloqueo_'.$tipoBloqueo->id)
                ->withInput();
        }

        $toastType = match ($resultado['status'] ?? 'failed') {
            'success' => 'success',
            'partial' => 'warning',
            'no_blocks' => 'info',
            default => 'error',
        };

        return toast($resultado['message'] ?? 'No fue posible procesar el levantamiento.', $toastType, $redirect);
    }
}
